<?php
/**
 * Network Admin UI Class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class omdm_Network_Admin {

    private string $plugin_name;
    private string $version;
    private wpdb   $db;

    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Netzwerk-Menü registrieren
     */
    public function add_network_menu(): void {
        add_menu_page(
            __( 'Domain Mapping', 'onartline-multisite-domain-mapping' ),
            __( 'Domain Mapping', 'onartline-multisite-domain-mapping' ),
            'manage_network',
            'onartline-multisite-domain-mapping',
            [ $this, 'render_overview' ],
            'dashicons-admin-site-alt3',
            30
        );

        add_submenu_page(
            'onartline-multisite-domain-mapping',
            __( 'Overview', 'onartline-multisite-domain-mapping' ),
            __( 'Overview', 'onartline-multisite-domain-mapping' ),
            'manage_network',
            'onartline-multisite-domain-mapping',
            [ $this, 'render_overview' ]
        );

        add_submenu_page(
            'onartline-multisite-domain-mapping',
            __( 'Add domain', 'onartline-multisite-domain-mapping' ),
            __( 'Add domain', 'onartline-multisite-domain-mapping' ),
            'manage_network',
            'onartline-multisite-domain-mapping-add',
            [ $this, 'render_add_domain' ]
        );

        add_submenu_page(
            'onartline-multisite-domain-mapping',
            __( 'Settings', 'onartline-multisite-domain-mapping' ),
            __( 'Settings', 'onartline-multisite-domain-mapping' ),
            'manage_network',
            'onartline-multisite-domain-mapping-settings',
            [ $this, 'render_settings' ]
        );

        add_action(
            'load-toplevel_page_onartline-multisite-domain-mapping',
            [ $this, 'handle_bulk_actions' ]
        );
    }

    /**
     * Bulk-Aktionen früh verarbeiten (vor HTML-Output)
     *
     * Hinweis: $_POST['action'] / $_POST['action2'] stammen aus der
     * WP_List_Table-Bulk-Action-Auswahl. Die eigentliche Nonce-Prüfung
     * erfolgt weiter unten via check_admin_referer(), sobald feststeht,
     * dass tatsächlich eine schreibende Aktion ausgeführt werden soll.
     */
    public function handle_bulk_actions(): void {
        if ( ! isset( $_POST['action'] ) && ! isset( $_POST['action2'] ) ) {
            return;
        }

        $action = '';

        if ( isset( $_POST['action'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird unten via check_admin_referer() geprüft.
            $posted_action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
            if ( '-1' !== $posted_action ) {
                $action = $posted_action;
            }
        } elseif ( isset( $_POST['action2'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird unten via check_admin_referer() geprüft.
            $posted_action2 = sanitize_text_field( wp_unslash( $_POST['action2'] ) );
            if ( '-1' !== $posted_action2 ) {
                $action = $posted_action2;
            }
        }

        if (
            'bulk_delete' === $action &&
            check_admin_referer( 'bulk-domains' ) &&
            ! empty( $_POST['domain_ids'] )
        ) {
            if ( ! current_user_can( 'manage_network' ) ) {
                wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
            }

            $ids     = array_map( 'intval', wp_unslash( $_POST['domain_ids'] ) );
            $table   = $this->db->base_prefix . 'omdm_domain_mapping';
            $domains = [];

            foreach ( $ids as $id ) {
                $mapping = $this->db->get_row(
                    $this->db->prepare( "SELECT domain FROM {$table} WHERE id = %d LIMIT 1", $id )
                );
                if ( $mapping ) {
                    $domains[] = $mapping->domain;
                }
                $this->db->delete( $table, [ 'id' => $id ], [ '%d' ] );
            }

            $count = count( $domains );

            if ( 1 === $count ) {
                $redirect_url = add_query_arg(
                    [
                        'omdm_deleted'        => '1',
                        'omdm_deleted_domain' => rawurlencode( $domains[0] ),
                    ],
                    network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping' )
                );
            } else {
                $redirect_url = add_query_arg(
                    [ 'omdm_bulk_deleted' => $count ],
                    network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping' )
                );
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
	    /**
     * Übersicht rendern
     */
    public function render_overview(): void {
        $list_table = new omdm_Domain_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e( 'Domain Mapping – Overview', 'onartline-multisite-domain-mapping' ); ?>
            </h1>

            <a href="<?php echo esc_url( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-add' ) ); ?>"
               class="page-title-action">
                <?php esc_html_e( 'Add domain', 'onartline-multisite-domain-mapping' ); ?>
            </a>

            <hr class="wp-header-end">

            <?php $this->render_notices(); ?>

            <form method="post">
                <?php
                wp_nonce_field( 'bulk-domains' );
                $list_table->search_box(
                    __( 'Search domains', 'onartline-multisite-domain-mapping' ),
                    'omdm-search'
                );
                $list_table->views();
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Domain hinzufügen / bearbeiten rendern
     *
     * $_GET['edit'] dient hier lediglich dazu, ein Formular zur
     * Ansicht vorzubefüllen – es löst keine schreibende Aktion aus.
     */
    public function render_add_domain(): void {
        $table = $this->db->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lesezugriff zur Formular-Vorbefüllung, keine Datenänderung.
        $edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
        $mapping = null;

        if ( $edit_id ) {
            $mapping = $this->db->get_row(
                $this->db->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $edit_id )
            );
        }
        ?>
        <div class="wrap">
            <h1>
                <?php echo $mapping
                    ? esc_html__( 'Edit domain', 'onartline-multisite-domain-mapping' )
                    : esc_html__( 'Add domain', 'onartline-multisite-domain-mapping' );
                ?>
            </h1>

            <?php $this->render_notices(); ?>

            <form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=omdm_save_domain' ) ); ?>">
                <?php wp_nonce_field( 'omdm_save_domain' ); ?>
                <?php if ( $mapping ) : ?>
                    <input type="hidden" name="omdm_id" value="<?php echo esc_attr( $mapping->id ); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Site', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <?php $sites = get_sites( [ 'number' => 500 ] ); ?>
                            <select name="omdm_blog_id">
                                <?php foreach ( $sites as $site ) : ?>
                                    <option value="<?php echo esc_attr( $site->blog_id ); ?>"
                                        <?php selected( $mapping ? $mapping->blog_id : '', $site->blog_id ); ?>>
                                        <?php echo esc_html( $site->blogname . ' (ID: ' . $site->blog_id . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Domain', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <input type="text" name="omdm_domain"
                                value="<?php echo esc_attr( $mapping->domain ?? '' ); ?>"
                                placeholder="<?php esc_attr_e( 'Domain without http:// or https://', 'onartline-multisite-domain-mapping' ); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Primary Domain', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_is_primary" value="1"
                                    <?php checked( $mapping->is_primary ?? 0, 1 ); ?>>
                                <?php esc_html_e( 'Set as primary domain', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'All other domains of this site will redirect to this one.', 'onartline-multisite-domain-mapping' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'HTTPS', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_force_https" value="1"
                                    <?php checked( $mapping->https ?? 0, 1 ); ?>>
                                <?php esc_html_e( 'Enforce HTTPS for this domain', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( $mapping
                    ? __( 'Update domain', 'onartline-multisite-domain-mapping' )
                    : __( 'Save domain', 'onartline-multisite-domain-mapping' )
                ); ?>
            </form>
        </div>
        <?php
    }
    /**
     * Einstellungen rendern
     */
    public function render_settings(): void {
        $server_ip    = get_site_option( 'omdm_server_ip', '' );
        $server_ipv6  = get_site_option( 'omdm_server_ipv6', '' );
        $server_cname = get_site_option( 'omdm_server_cname', '' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Domain Mapping – Settings', 'onartline-multisite-domain-mapping' ); ?></h1>

            <?php $this->render_notices(); ?>

            <form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=omdm_save_settings' ) ); ?>">
                <?php wp_nonce_field( 'omdm_save_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Default HTTPS', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_force_https" value="1"
                                    <?php checked( get_site_option( 'omdm_force_https', false ), true ); ?>>
                                <?php esc_html_e( 'Enforce HTTPS for all domains by default', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( '301 redirect', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_301_redirect" value="1"
                                    <?php checked( get_site_option( 'omdm_301_redirect', false ), true ); ?>>
                                <?php esc_html_e( 'Enable 301 redirect for non-primary domains', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Site admin domain mapping', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_allow_site_mapping" value="1"
                                    <?php checked( get_site_option( 'omdm_allow_site_mapping', false ), true ); ?>>
                                <?php esc_html_e( 'Allow site admins to add their own domains', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'If enabled, a "Domain Mapping" menu item will appear in the dashboard of each subsite.', 'onartline-multisite-domain-mapping' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr>
                            <h2><?php esc_html_e( 'DNS information', 'onartline-multisite-domain-mapping' ); ?></h2>
                            <p><?php esc_html_e(
                                'As a super admin, you can specify here the IP address or CNAME domain that users should point their DNS entry to. This information is for informational purposes only and will be displayed to your users.',
                                'onartline-multisite-domain-mapping'
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Server IP address', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <input type="text" name="omdm_server_ip"
                                value="<?php echo esc_attr( $server_ip ); ?>"
                                placeholder="<?php esc_attr_e( 'e.g. 192.168.1.1 or multiple, comma-separated', 'onartline-multisite-domain-mapping' ); ?>"
                                class="regular-text">
                            <p class="description">
                                <?php esc_html_e(
                                    'IPv4 address for the DNS A record. Enter multiple IPs comma-separated (e.g. for round-robin DNS).',
                                    'onartline-multisite-domain-mapping'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Server IPv6 address', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <input type="text" name="omdm_server_ipv6"
                                value="<?php echo esc_attr( $server_ipv6 ); ?>"
                                placeholder="<?php esc_attr_e( 'e.g. 2001:db8::1 or multiple, comma-separated', 'onartline-multisite-domain-mapping' ); ?>"
                                class="regular-text">
                            <p class="description">
                                <?php esc_html_e(
                                    'IPv6 address for the DNS AAAA record. Enter multiple IPs comma-separated (e.g. for round-robin DNS). Leave empty if IPv6 is not supported by your server.',
                                    'onartline-multisite-domain-mapping'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Server CNAME domain', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <input type="text" name="omdm_server_cname"
                                value="<?php echo esc_attr( $server_cname ); ?>"
                                placeholder="<?php esc_attr_e( 'e.g. proxy.example.com', 'onartline-multisite-domain-mapping' ); ?>"
                                class="regular-text">
                            <p class="description">
                                <?php esc_html_e(
                                    'CNAME domain as an alternative to the IP address. Enter internationalized domain names in Punycode format. Note: If a CNAME domain is provided, the IP address and IPv6 address fields are ignored.',
                                    'onartline-multisite-domain-mapping'
                                ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <hr>
                            <h2><?php esc_html_e( 'Uninstallation', 'onartline-multisite-domain-mapping' ); ?></h2>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Remove data on deletion', 'onartline-multisite-domain-mapping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="omdm_delete_data_on_uninstall" value="1"
                                    <?php checked( get_site_option( 'omdm_delete_data_on_uninstall', false ), true ); ?>>
                                <?php esc_html_e( 'Remove database tables (domain mappings, login tokens) when the plugin is deleted', 'onartline-multisite-domain-mapping' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e(
                                    'Warning: If enabled, all domain mappings will be permanently removed when the plugin is deleted via the plugins overview. Simply deactivating the plugin will always keep the data intact.',
                                    'onartline-multisite-domain-mapping'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save settings', 'onartline-multisite-domain-mapping' ) ); ?>
            </form>
        </div>
        <?php
    }
	    /**
     * Domain speichern
     */
    public function save_domain(): void {
        check_admin_referer( 'omdm_save_domain' );

        if ( ! current_user_can( 'manage_network' ) ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }

        $id          = isset( $_POST['omdm_id'] ) ? (int) $_POST['omdm_id'] : 0;
        $blog_id     = isset( $_POST['omdm_blog_id'] ) ? (int) $_POST['omdm_blog_id'] : 0;
        $domain      = isset( $_POST['omdm_domain'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['omdm_domain'] ) ) ) : '';
        $is_primary  = isset( $_POST['omdm_is_primary'] ) ? 1 : 0;
        $force_https = isset( $_POST['omdm_force_https'] ) ? 1 : 0;

        if ( ! $blog_id || ! $domain ) {
            wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-add&omdm_error=empty' ) );
            exit;
        }

        if ( ! preg_match( '/^[a-z0-9\-\.]+\.[a-z]{2,}$/', $domain ) ) {
            wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-add&omdm_error=invalid' ) );
            exit;
        }

        $table = $this->db->base_prefix . 'omdm_domain_mapping';

        // Netzwerkweite Eindeutigkeitsprüfung: Domain darf nicht bereits einem anderen Eintrag zugeordnet sein.
        $existing_id = $this->db->get_var(
            $this->db->prepare(
                "SELECT id FROM {$table} WHERE domain = %s LIMIT 1",
                $domain
            )
        );

        if ( $existing_id && (int) $existing_id !== $id ) {
            wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-add&omdm_error=duplicate' ) );
            exit;
        }

        if ( $is_primary ) {
            $this->db->update(
                $table,
                [ 'is_primary' => 0 ],
                [ 'blog_id'    => $blog_id ],
                [ '%d' ],
                [ '%d' ]
            );
        }

        $data = [
            'blog_id'    => $blog_id,
            'domain'     => $domain,
            'is_primary' => $is_primary,
            'https'      => $force_https,
        ];

        if ( $id ) {
            $this->db->update( $table, $data, [ 'id' => $id ], [ '%d', '%s', '%d', '%d' ], [ '%d' ] );
        } else {
            $data['is_reachable'] = 1;

            $this->db->insert( $table, $data, [ '%d', '%s', '%d', '%d', '%d' ] );
        }

        wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping&omdm_success=1' ) );
        exit;
    }

    /**
     * Domain löschen
     */
    public function delete_domain(): void {
        $id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

        if ( ! $id ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }

        check_admin_referer( 'omdm_delete_domain_' . $id );

        if ( ! current_user_can( 'manage_network' ) ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }

        $table   = $this->db->base_prefix . 'omdm_domain_mapping';
        $mapping = $this->db->get_row(
            $this->db->prepare( "SELECT domain FROM {$table} WHERE id = %d LIMIT 1", $id )
        );

        $this->db->delete( $table, [ 'id' => $id ], [ '%d' ] );

        $domain_name = $mapping ? $mapping->domain : '';

        if ( $domain_name ) {
            $redirect_url = add_query_arg(
                [
                    'omdm_deleted'        => '1',
                    'omdm_deleted_domain' => rawurlencode( $domain_name ),
                ],
                network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping' )
            );
        } else {
            $redirect_url = network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping&omdm_success=1' );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Validiert eine kommagetrennte Liste von IP-Adressen.
     *
     * @param string $input       Roh-Eingabe, kommagetrennt.
     * @param int    $filter_flag FILTER_FLAG_IPV4 oder FILTER_FLAG_IPV6.
     * @return string|false Bereinigte, kommagetrennte Liste oder false bei ungültiger Eingabe.
     */
    private function validate_ip_list( string $input, int $filter_flag ) {
        $input = trim( $input );

        if ( '' === $input ) {
            return '';
        }

        $ips       = array_map( 'trim', explode( ',', $input ) );
        $valid_ips = [];

        foreach ( $ips as $ip ) {
            if ( '' === $ip ) {
                continue;
            }
            if ( ! filter_var( $ip, FILTER_VALIDATE_IP, $filter_flag ) ) {
                return false;
            }
            $valid_ips[] = $ip;
        }

        return implode( ', ', $valid_ips );
    }

    /**
     * Einstellungen speichern
     */
    public function save_settings(): void {
        check_admin_referer( 'omdm_save_settings' );

        if ( ! current_user_can( 'manage_network' ) ) {
            wp_die( esc_html__( 'No permission.', 'onartline-multisite-domain-mapping' ) );
        }

        update_site_option( 'omdm_force_https', isset( $_POST['omdm_force_https'] ) );
        update_site_option( 'omdm_301_redirect', isset( $_POST['omdm_301_redirect'] ) );
        update_site_option( 'omdm_allow_site_mapping', isset( $_POST['omdm_allow_site_mapping'] ) );
        update_site_option( 'omdm_delete_data_on_uninstall', isset( $_POST['omdm_delete_data_on_uninstall'] ) );

        $server_ip_raw = isset( $_POST['omdm_server_ip'] )
            ? sanitize_text_field( wp_unslash( $_POST['omdm_server_ip'] ) )
            : '';

        $server_ipv6_raw = isset( $_POST['omdm_server_ipv6'] )
            ? sanitize_text_field( wp_unslash( $_POST['omdm_server_ipv6'] ) )
            : '';

        $server_cname_raw = isset( $_POST['omdm_server_cname'] )
            ? strtolower( sanitize_text_field( wp_unslash( $_POST['omdm_server_cname'] ) ) )
            : '';

        $server_ip   = $this->validate_ip_list( $server_ip_raw, FILTER_FLAG_IPV4 );
        $server_ipv6 = $this->validate_ip_list( $server_ipv6_raw, FILTER_FLAG_IPV6 );

        if ( false === $server_ip ) {
            wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-settings&omdm_error=invalid_ip' ) );
            exit;
        }

        if ( false === $server_ipv6 ) {
            wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-settings&omdm_error=invalid_ipv6' ) );
            exit;
        }

        $server_cname = '';

        if ( '' !== $server_cname_raw ) {
            if ( ! preg_match( '/^[a-z0-9\-\.]+\.[a-z]{2,}$/', $server_cname_raw ) ) {
                wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-settings&omdm_error=invalid_cname' ) );
                exit;
            }
            $server_cname = $server_cname_raw;
        }

        update_site_option( 'omdm_server_ip', $server_ip );
        update_site_option( 'omdm_server_ipv6', $server_ipv6 );
        update_site_option( 'omdm_server_cname', $server_cname );

        wp_safe_redirect( network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-settings&omdm_success=1' ) );
        exit;
    }

    /**
     * Admin-Hinweise (Erfolg/Fehler) anzeigen
     *
     * Die hier ausgewerteten $_GET-Parameter dienen ausschließlich der
     * Anzeige von Rückmeldungen nach einem Redirect (z. B. nach Speichern
     * oder Löschen) und lösen selbst keine Datenänderung aus.
     */
    private function render_notices(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
        if ( isset( $_GET['omdm_success'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Changes saved successfully.', 'onartline-multisite-domain-mapping' ); ?></p>
            </div>
            <?php
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
        if ( isset( $_GET['omdm_deleted'] ) ) {
            $domain_name = '';

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
            if ( isset( $_GET['omdm_deleted_domain'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
                $domain_name = sanitize_text_field( wp_unslash( $_GET['omdm_deleted_domain'] ) );
            }
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    if ( $domain_name ) {
                        printf(
                            /* translators: %s: domain name */
                            esc_html__( 'Domain "%s" has been deleted.', 'onartline-multisite-domain-mapping' ),
                            esc_html( $domain_name )
                        );
                    } else {
                        esc_html_e( 'Domain has been deleted.', 'onartline-multisite-domain-mapping' );
                    }
                    ?>
                </p>
            </div>
            <?php
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
        if ( isset( $_GET['omdm_bulk_deleted'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
            $count = (int) $_GET['omdm_bulk_deleted'];
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: %d: number of deleted domains */
                            _n( '%d domain has been deleted.', '%d domains have been deleted.', $count, 'onartline-multisite-domain-mapping' ),
                            $count
                        )
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
        if ( isset( $_GET['omdm_error'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeige-Parameter nach Redirect, keine Datenänderung.
            $error = sanitize_text_field( wp_unslash( $_GET['omdm_error'] ) );

            $message = match ( $error ) {
                'empty'         => __( 'Please fill in all required fields.', 'onartline-multisite-domain-mapping' ),
                'invalid'       => __( 'Please enter a valid domain (e.g. example.com).', 'onartline-multisite-domain-mapping' ),
                'duplicate'     => __( 'This domain is already assigned to another site.', 'onartline-multisite-domain-mapping' ),
                'invalid_ip'    => __( 'Please enter a valid IPv4 address (e.g. 192.168.1.1).', 'onartline-multisite-domain-mapping' ),
                'invalid_ipv6'  => __( 'Please enter a valid IPv6 address (e.g. 2001:db8::1).', 'onartline-multisite-domain-mapping' ),
                'invalid_cname' => __( 'Please enter a valid CNAME domain (e.g. proxy.example.com).', 'onartline-multisite-domain-mapping' ),
                default         => __( 'An error occurred. Please try again.', 'onartline-multisite-domain-mapping' ),
            };
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
            <?php
        }
    }
}