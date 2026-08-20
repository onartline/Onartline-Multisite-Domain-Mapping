<?php
/**
 * Site Settings Class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class omdm_Site_Settings {


    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_post_omdm_save_site_domain', array( $this, 'handle_save' ) );
    }


    public function register_menu() {
        add_menu_page(
            __( 'Domain Mapping', 'onartline-multisite-domain-mapping' ),
            __( 'Domain Mapping', 'onartline-multisite-domain-mapping' ),
            'manage_options',
            'omdm-site-settings',
            array( $this, 'render_page' ),
            'dashicons-admin-site',
            80
        );
    }


    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }


        global $wpdb;
        $site_id = get_current_blog_id();
        $table   = $wpdb->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Tabellenname ist fest definiert, kein Nutzer-Input.
        $domains = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table ist ein fest definierter, plugin-eigener Tabellenname.
                "SELECT * FROM {$table} WHERE blog_id = %d ORDER BY is_primary DESC",
                $site_id
            )
        );


        $server_ip    = get_site_option( 'omdm_server_ip', '' );
        $server_cname = get_site_option( 'omdm_server_cname', '' );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Domain Mapping', 'onartline-multisite-domain-mapping' ); ?></h1>

            <?php
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige einer Meldung nach Redirect, keine Datenverarbeitung.
            if ( isset( $_GET['omdm_saved'] ) && $_GET['omdm_saved'] === '1' ) :
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Domain saved successfully.', 'onartline-multisite-domain-mapping' ); ?></p>
                </div>
                <?php
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige einer Meldung nach Redirect, keine Datenverarbeitung.
            elseif ( isset( $_GET['omdm_error'] ) ) :
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Error saving domain.', 'onartline-multisite-domain-mapping' ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $server_ip || $server_cname ) : ?>
                <div class="notice notice-info">
                    <p>
                        <strong><?php esc_html_e( 'DNS Note:', 'onartline-multisite-domain-mapping' ); ?></strong>
                        <?php if ( $server_cname ) : ?>
                            <?php esc_html_e( 'Please set up a CNAME record pointing to:', 'onartline-multisite-domain-mapping' ); ?>
                            <code><?php echo esc_html( $server_cname ); ?></code>
                        <?php elseif ( $server_ip ) : ?>
                            <?php esc_html_e( 'Please set up an A record pointing to:', 'onartline-multisite-domain-mapping' ); ?>
                            <code><?php echo esc_html( $server_ip ); ?></code>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <h2><?php esc_html_e( 'My Domains', 'onartline-multisite-domain-mapping' ); ?></h2>

            <?php if ( ! empty( $domains ) ) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Domain', 'onartline-multisite-domain-mapping' ); ?></th>
                            <th><?php esc_html_e( 'Primary', 'onartline-multisite-domain-mapping' ); ?></th>
                            <th><?php esc_html_e( 'HTTPS', 'onartline-multisite-domain-mapping' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $domains as $domain ) : ?>
                            <tr>
                                <td><?php echo esc_html( $domain->domain ); ?></td>
                                <td><?php echo $domain->is_primary ? '✔' : '—'; ?></td>
                                <td><?php echo $domain->https ? '✔' : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
            <?php else : ?>
                <p><?php esc_html_e( 'No domain has been added yet.', 'onartline-multisite-domain-mapping' ); ?></p>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Add domain', 'onartline-multisite-domain-mapping' ); ?></h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'omdm_save_site_domain', 'omdm_nonce' ); ?>
                <input type="hidden" name="action" value="omdm_save_site_domain">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="omdm_domain"><?php esc_html_e( 'Domain', 'onartline-multisite-domain-mapping' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="omdm_domain"
                                   name="omdm_domain"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e( 'Domain without http:// or https://', 'onartline-multisite-domain-mapping' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enforce HTTPS', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_https" value="1">
                                <?php esc_html_e( 'Enforce HTTPS for this domain', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save domain', 'onartline-multisite-domain-mapping' ) ); ?>
            </form>
        </div>
        <?php
    }


    public function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }

        if ( ! isset( $_POST['omdm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['omdm_nonce'] ) ), 'omdm_save_site_domain' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'onartline-multisite-domain-mapping' ) );
        }

        $domain  = isset( $_POST['omdm_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['omdm_domain'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce bereits oben geprüft.
        $https   = isset( $_POST['omdm_https'] ) ? 1 : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce bereits oben geprüft.
        $site_id = get_current_blog_id();

        if ( empty( $domain ) || ! preg_match( '/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain ) ) {
            wp_safe_redirect( add_query_arg( 'omdm_error', '1', admin_url( 'admin.php?page=omdm-site-settings' ) ) );
            exit;
        }

        global $wpdb;
        $table = $wpdb->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Tabellenname ist fest definiert, kein Nutzer-Input.
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table ist ein fest definierter, plugin-eigener Tabellenname.
                "SELECT COUNT(*) FROM {$table} WHERE domain = %s",
                $domain
            )
        );

        if ( $exists ) {
            wp_safe_redirect( add_query_arg( 'omdm_error', '1', admin_url( 'admin.php?page=omdm-site-settings' ) ) );
            exit;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert in plugin-eigene Tabelle, Caching nicht erforderlich.
        $result = $wpdb->insert(
            $table,
            array(
                'blog_id'      => $site_id,
                'domain'       => $domain,
                'is_primary'   => 0,
                'https'        => $https,
                'is_reachable' => 1,
            ),
            array( '%d', '%s', '%d', '%d', '%d' )
        );

        if ( $result === false ) {
            wp_safe_redirect( add_query_arg( 'omdm_error', '1', admin_url( 'admin.php?page=omdm-site-settings' ) ) );
            exit;
        }

        omdm_Domain_Mapper::invalidate_cache( $domain, $site_id );

        wp_safe_redirect( add_query_arg( 'omdm_saved', '1', admin_url( 'admin.php?page=omdm-site-settings' ) ) );
        exit;
    }
}