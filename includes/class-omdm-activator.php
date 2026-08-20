<?php
/**
 * Activator Class
 *
 * Wird beim Aktivieren des Plugins ausgeführt.
 * Kopiert sunrise.php nach wp-content/ und zeigt Admin-Notices.
 *
 * @package Onartline_Multisite_Domain_Mapping
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class omdm_Activator {


    /**
     * Marker, der die sunrise.php als vom Plugin verwaltet kennzeichnet.
     */
    const SUNRISE_MARKER = 'omdm_SUNRISE_MARKER';


    /**
     * Marker, den Nutzer in eine individuell angepasste sunrise.php eintragen
     * können, um automatische Überschreibungen durch das Plugin zu verhindern.
     */
    const CUSTOM_MARKER = 'omdm_CUSTOM_SUNRISE_MARKER';


    /**
     * Wird bei Plugin-Aktivierung aufgerufen.
     */
    public static function activate(): void {


        // ── Multisite-Voraussetzung prüfen ────────────────────────────────────
        if ( ! is_multisite() ) {
            set_transient( 'omdm_requires_multisite', true, 300 );
            return;
        }


        // ── Alte Transients bereinigen ────────────────────────────────────────
        self::clear_sunrise_transients();


        // ── Datenbank-Tabellen anlegen ────────────────────────────────────────
        self::create_tables();


        // ── Standard-Optionen setzen ──────────────────────────────────────────
        self::set_default_options();


        // ── WP_Filesystem initialisieren ──────────────────────────────────────
        require_once ABSPATH . 'wp-admin/includes/file.php';


        if ( ! WP_Filesystem() ) {
            set_transient( 'omdm_sunrise_not_writable', true, 300 );
            return;
        }


        global $wp_filesystem;


        $source      = plugin_dir_path( dirname( __FILE__ ) ) . 'sunrise.php';
        $destination = WP_CONTENT_DIR . '/sunrise.php';


        // sunrise.php bereits vorhanden → Herkunft und Version prüfen
        if ( $wp_filesystem->exists( $destination ) ) {
            $existing_content = $wp_filesystem->get_contents( $destination );


            if ( false !== $existing_content && false !== strpos( $existing_content, self::SUNRISE_MARKER ) ) {


                // Individuell angepasste sunrise.php → nicht anfassen.
                if ( false !== strpos( $existing_content, self::CUSTOM_MARKER ) ) {
                    set_transient( 'omdm_sunrise_custom_detected', true, 300 );
                    return;
                }


                // Eigene, unveränderte sunrise.php – Version vergleichen.
                $source_content = $wp_filesystem->exists( $source )
                    ? $wp_filesystem->get_contents( $source )
                    : false;


                $existing_version = self::get_sunrise_marker_version( $existing_content );
                $source_version   = $source_content ? self::get_sunrise_marker_version( $source_content ) : '0';


                if ( false !== $source_content && version_compare( $source_version, $existing_version, '>' ) ) {
                    if ( $wp_filesystem->is_writable( WP_CONTENT_DIR ) && $wp_filesystem->copy( $source, $destination, true ) ) {
                        $wp_filesystem->delete( $source );
                        set_transient( 'omdm_sunrise_updated', true, 60 );
                    } else {
                        set_transient( 'omdm_sunrise_update_failed', true, 300 );
                    }
                } else {
                    set_transient( 'omdm_sunrise_already_exists', true, 60 );
                }
            } else {
                set_transient( 'omdm_sunrise_foreign_exists', true, 300 );
            }
            return;
        }


        if ( ! $wp_filesystem->exists( $source ) ) {
            set_transient( 'omdm_sunrise_source_missing', true, 300 );
            return;
        }


        if ( ! $wp_filesystem->is_writable( WP_CONTENT_DIR ) ) {
            set_transient( 'omdm_sunrise_not_writable', true, 300 );
            return;
        }


        if ( $wp_filesystem->copy( $source, $destination, true ) ) {
            $wp_filesystem->delete( $source );
            set_transient( 'omdm_sunrise_copied', true, 60 );
        } else {
            set_transient( 'omdm_sunrise_copy_failed', true, 300 );
        }
    }


    /**
     * Deaktiviert das Plugin automatisch, falls es aufgrund des
     * WordPress-eigenen Aktivierungsablaufs auf einer Single-Site-Installation
     * aktiv bleibt, und sorgt so für eine passende Hinweis-Anzeige statt
     * eines harten Abbruchs während der Aktivierung.
     *
     * Wird über 'admin_init' in der Haupt-Plugin-Datei ausgeführt.
     */
    public static function maybe_deactivate_self(): void {
        if ( is_multisite() || ! get_transient( 'omdm_requires_multisite' ) ) {
            return;
        }


        require_once ABSPATH . 'wp-admin/includes/plugin.php';


        $plugin_file = plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . 'onartline-multisite-domain-mapping.php' );


        if ( is_plugin_active( $plugin_file ) ) {
            deactivate_plugins( $plugin_file );
        }
    }


    /**
     * Löscht alle sunrise-bezogenen Transients, damit bei einer erneuten
     * Aktivierung keine veraltete Meldung mit dem neuen Status kollidiert.
     */
    private static function clear_sunrise_transients(): void {
        $transients = array(
            'omdm_sunrise_copied',
            'omdm_sunrise_updated',
            'omdm_sunrise_update_failed',
            'omdm_sunrise_foreign_exists',
            'omdm_sunrise_not_writable',
            'omdm_sunrise_source_missing',
            'omdm_sunrise_copy_failed',
            'omdm_sunrise_already_exists',
            'omdm_sunrise_custom_detected',
        );


        foreach ( $transients as $transient ) {
            delete_transient( $transient );
        }
    }


    /**
     * Liest die Versionsnummer aus dem omdm_SUNRISE_MARKER-Kommentarblock aus.
     */
    private static function get_sunrise_marker_version( string $content ): string {
        if ( preg_match( '/omdm_SUNRISE_VERSION:\s*([0-9.]+)/', $content, $matches ) ) {
            return $matches[1];
        }
        return '0';
    }


    /**
     * Datenbank-Tabellen erstellen.
     */
    private static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';


        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->base_prefix;


        $sql1 = "CREATE TABLE {$prefix}omdm_domain_mapping (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) UNSIGNED NOT NULL,
            domain varchar(255) NOT NULL,
            is_primary tinyint(1) NOT NULL DEFAULT 0,
            https tinyint(1) NOT NULL DEFAULT 0,
            is_reachable tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY blog_id (blog_id),
            KEY domain (domain)
        ) $charset;";


        $sql2 = "CREATE TABLE {$prefix}omdm_login_tokens (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    token varchar(64) NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    blog_id bigint(20) UNSIGNED NOT NULL,
    redirect_to varchar(2083) NOT NULL DEFAULT '',
    expires_at datetime NOT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY token (token),
    KEY user_id (user_id),
    KEY blog_id (blog_id),
    KEY expires_at (expires_at)
        ) $charset;";


        dbDelta( $sql1 );
        dbDelta( $sql2 );
    }


    /**
     * Standard-Optionen setzen.
     */
    private static function set_default_options(): void {
        if ( ! get_site_option( 'omdm_version' ) ) {
            add_site_option( 'omdm_version', omdm_VERSION );
        }
        if ( false === get_site_option( 'omdm_force_https' ) ) {
            add_site_option( 'omdm_force_https', 0 );
        }
        if ( false === get_site_option( 'omdm_301_redirect' ) ) {
            add_site_option( 'omdm_301_redirect', 0 );
        }
        if ( false === get_site_option( 'omdm_allow_site_mapping' ) ) {
            add_site_option( 'omdm_allow_site_mapping', 0 );
        }
        if ( false === get_site_option( 'omdm_delete_data_on_uninstall' ) ) {
            add_site_option( 'omdm_delete_data_on_uninstall', 0 );
        }
        if ( false === get_site_option( 'omdm_server_ip' ) ) {
            add_site_option( 'omdm_server_ip', '' );
        }
        if ( false === get_site_option( 'omdm_server_ipv6' ) ) {
            add_site_option( 'omdm_server_ipv6', '' );
        }
        if ( false === get_site_option( 'omdm_server_cname' ) ) {
            add_site_option( 'omdm_server_cname', '' );
        }
    }
    /**
     * Prüft dynamisch, ob SUNRISE bereits korrekt in der wp-config.php
     * gesetzt ist, und liefert passenden HTML-Hinweis zurück.
     */
    private static function get_sunrise_config_notice_html(): string {
        if ( defined( 'SUNRISE' ) && SUNRISE ) {
            return '<p>✓ '
                . esc_html__(
                    'The SUNRISE constant is already correctly set in wp-config.php.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p>';
        }


        return '<p><strong>' . esc_html__( 'Note:', 'onartline-multisite-domain-mapping' ) . '</strong> '
            . esc_html__(
                'The SUNRISE constant is not set in wp-config.php (or is set to false). Please add the following line – directly before require_once ABSPATH . \'wp-settings.php\';',
                'onartline-multisite-domain-mapping'
            )
            . '<br><code>define( \'SUNRISE\', true );</code></p>';
    }

    /**
     * Prüft, ob im Plugin-Ordner noch eine ungenutzte sunrise.php liegt,
     * und liefert ggf. einen Hinweis zum manuellen Löschen zurück.
     */
    private static function get_source_cleanup_notice_html(): string {
        $source = plugin_dir_path( dirname( __FILE__ ) ) . 'sunrise.php';


        if ( ! file_exists( $source ) ) {
            return '';
        }


        return '<p>' . sprintf(
        /* translators: %s: Dateipfad zur unbenutzten sunrise.php im Plugin-Ordner. */
            esc_html__(
                'The sunrise.php file belonging to this plugin still exists in the plugin folder. It was not automatically removed. Please delete it manually if you are using a customized sunrise.php in wp-content/: %s',
                'onartline-multisite-domain-mapping'
            ),
            '<code>' . esc_html( $source ) . '</code>'
        ) . '</p>';
    }


    /**
     * Erstellt die abgesicherte URL für den "Installation fortsetzen"-Button.
     */
    private static function get_retry_button_url(): string {
        return wp_nonce_url(
            add_query_arg( 'action', 'omdm_retry_sunrise', admin_url( 'admin-post.php' ) ),
            'omdm_retry_sunrise_install'
        );
    }


    /**
     * Wird aufgerufen, wenn der Nutzer im Admin-Notice auf
     * "Installation jetzt fortsetzen" klickt.
     */
    public static function retry_sunrise_install(): void {
        check_admin_referer( 'omdm_retry_sunrise_install' );


        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }


        self::activate();


        $redirect = wp_get_referer() ? wp_get_referer() : network_admin_url( 'plugins.php' );
        wp_safe_redirect( $redirect );
        exit;
    }


    /**
     * Admin-Notices nach Aktivierung anzeigen.
     */
    public static function activation_notices(): void {


        if ( get_transient( 'omdm_requires_multisite' ) ) {
            delete_transient( 'omdm_requires_multisite' );
            echo '<div class="notice notice-error is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'This plugin requires a WordPress Multisite installation. Please set up a network in your WordPress installation first, then reactivate the plugin to manage your domains. No database tables or files have been created.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p></div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_copied' ) ) {
            delete_transient( 'omdm_sunrise_copied' );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'sunrise.php was successfully copied to wp-content/ and removed from the plugin folder.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p>'
                . wp_kses_post( self::get_sunrise_config_notice_html() )
                . '</div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_updated' ) ) {
            delete_transient( 'omdm_sunrise_updated' );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'An outdated sunrise.php was automatically replaced by the current version from the plugin folder.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p>'
                . wp_kses_post( self::get_sunrise_config_notice_html() )
                . '</div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_update_failed' ) ) {
            delete_transient( 'omdm_sunrise_update_failed' );
            $source_path = plugin_dir_path( dirname( __FILE__ ) ) . 'sunrise.php';
            echo '<div class="notice notice-warning is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'A newer sunrise.php was found in the plugin folder, but it could not be copied automatically to wp-content/. Please update manually:',
                    'onartline-multisite-domain-mapping'
                )
                . '</p><ol>'
                . '<li>' . sprintf(
                    /* translators: 1: Quellpfad, 2: Zielpfad. */
                    esc_html__( 'Copy %1$s to %2$s', 'onartline-multisite-domain-mapping' ),
                    '<code>' . esc_html( $source_path ) . '</code>',
                    '<code>' . esc_html( WP_CONTENT_DIR . '/sunrise.php' ) . '</code>'
                ) . '</li>'
                . '</ol></div>';
            return;
        }


        // ── Individuell angepasste sunrise.php erkannt ────────────────────────
        if ( get_transient( 'omdm_sunrise_custom_detected' ) ) {
            delete_transient( 'omdm_sunrise_custom_detected' );
            echo '<div class="notice notice-warning is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'Custom sunrise.php detected. Please back up your customized sunrise.php beforehand and adjust it again to your needs after the update, so that the plugin continues to work properly.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p>'
                . wp_kses_post( self::get_sunrise_config_notice_html() )
                . '</div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_foreign_exists' ) ) {
            delete_transient( 'omdm_sunrise_foreign_exists' );
            echo '<div class="notice notice-error is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'An existing sunrise.php was found in wp-content/ that does not originate from this plugin. It was NOT overwritten to avoid conflicts.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p><p>'
                . esc_html__(
                    'Please check manually whether the existing file is compatible with this plugin, and replace it yourself if necessary.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p>'
                . wp_kses_post( self::get_sunrise_config_notice_html() )
                . wp_kses_post( self::get_source_cleanup_notice_html() )
                . '</div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_not_writable' ) ) {
            delete_transient( 'omdm_sunrise_not_writable' );
            $source_path = plugin_dir_path( dirname( __FILE__ ) ) . 'sunrise.php';
            echo '<div class="notice notice-warning is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'wp-content/ is currently not writable. Once the write permissions have been adjusted, you can continue the installation here – without having to deactivate and reactivate the plugin:',
                    'onartline-multisite-domain-mapping'
                )
                . '</p><p>'
                . '<a href="' . esc_url( self::get_retry_button_url() ) . '" class="button button-primary">'
                . esc_html__( 'Continue installation now', 'onartline-multisite-domain-mapping' )
                . '</a>'
                . '</p><p>'
                . esc_html__( 'Alternatively, manual installation is also possible:', 'onartline-multisite-domain-mapping' )
                . '</p><ol>'
                . '<li>' . sprintf(
                    /* translators: 1: Quellpfad, 2: Zielpfad. */
                    esc_html__( 'Copy %1$s to %2$s', 'onartline-multisite-domain-mapping' ),
                    '<code>' . esc_html( $source_path ) . '</code>',
                    '<code>' . esc_html( WP_CONTENT_DIR . '/sunrise.php' ) . '</code>'
                ) . '</li>'
                . '<li>'
                . esc_html__( 'Add the following line to your wp-config.php – directly before require_once ABSPATH . \'wp-settings.php\';', 'onartline-multisite-domain-mapping' )
                . '<br><code>define( \'SUNRISE\', true );</code>'
                . '</li>'
                . '</ol></div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_source_missing' ) ) {
            delete_transient( 'omdm_sunrise_source_missing' );
            echo '<div class="notice notice-error is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'sunrise.php was not found in the plugin folder. Please download and install the plugin again.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p></div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_copy_failed' ) ) {
            delete_transient( 'omdm_sunrise_copy_failed' );
            $source_path = plugin_dir_path( dirname( __FILE__ ) ) . 'sunrise.php';
            echo '<div class="notice notice-error is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'sunrise.php could not be copied automatically. Please install it manually:',
                    'onartline-multisite-domain-mapping'
                )
                . '</p><ol>'
                . '<li>' . sprintf(
                    /* translators: 1: Quellpfad, 2: Zielpfad. */
                    esc_html__( 'Copy %1$s to %2$s', 'onartline-multisite-domain-mapping' ),
                    '<code>' . esc_html( $source_path ) . '</code>',
                    '<code>' . esc_html( WP_CONTENT_DIR . '/sunrise.php' ) . '</code>'
                ) . '</li>'
                . '<li>'
                . esc_html__( 'Add the following line to your wp-config.php – directly before require_once ABSPATH . \'wp-settings.php\';', 'onartline-multisite-domain-mapping' )
                . '<br><code>define( \'SUNRISE\', true );</code>'
                . '</li>'
                . '</ol></div>';
            return;
        }


        if ( get_transient( 'omdm_sunrise_already_exists' ) ) {
            delete_transient( 'omdm_sunrise_already_exists' );
            echo '<div class="notice notice-info is-dismissible"><p>'
                . '<strong>Onartline Multisite Domain Mapping:</strong> '
                . esc_html__(
                    'sunrise.php already exists in wp-content/.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p>'
                . wp_kses_post( self::get_sunrise_config_notice_html() )
                . '</div>';
            return;
        }
    }
}