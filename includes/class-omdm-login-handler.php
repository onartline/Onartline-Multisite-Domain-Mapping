<?php
/**
 * Login Handler Class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class omdm_Login_Handler {

    private wpdb $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function handle_login( string $user_login, WP_User $user ): void {
        $blog_id = get_current_blog_id();
        $primary = $this->get_primary_domain( $blog_id );

        if ( ! $primary ) {
            return;
        }

        $token    = $this->create_login_token( $user->ID, $blog_id );
        $scheme   = $primary->https ? 'https' : 'http';
        $redirect = admin_url();
        $target   = $scheme . '://' . $primary->domain . '/?omdm_token=' . $token . '&redirect_to=' . rawurlencode( $redirect );

        add_filter(
            'allowed_redirect_hosts',
            function ( array $hosts ) use ( $primary ) {
                $hosts[] = $primary->domain;
                return $hosts;
            }
        );

        wp_safe_redirect( $target );
        exit;
    }

    /**
     * Token automatisch in Admin-URLs zu anderen Blogs einschleusen.
     * Deckt Admin-Bar, "Meine Websites"-Übersicht und alle weiteren
     * Stellen ab, die get_admin_url() für fremde Blogs nutzen.
     */
    public function filter_admin_url( string $url, string $path, ?int $blog_id ): string {
        if ( ! is_user_logged_in() ) {
            return $url;
        }

        $blog_id = $blog_id ?? get_current_blog_id();

        $current_blog_id = get_current_blog_id();

        if ( ! $blog_id || $blog_id === $current_blog_id ) {
            return $url;
        }

        static $tokens = [];

        if ( ! isset( $tokens[ $blog_id ] ) ) {
            $tokens[ $blog_id ] = $this->create_login_token( get_current_user_id(), $blog_id );
        }

        return add_query_arg( 'omdm_token', $tokens[ $blog_id ], $url );
    }

    public function filter_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
        $blog_id = get_current_blog_id();
        $primary = $this->get_primary_domain( $blog_id );

        if ( ! $primary ) {
            return $login_url;
        }

        $scheme    = $primary->https ? 'https' : 'http';
        $login_url = $scheme . '://' . $primary->domain . '/wp-login.php';

        if ( $redirect ) {
            $login_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $login_url );
        }

        if ( $force_reauth ) {
            $login_url = add_query_arg( 'reauth', '1', $login_url );
        }

        return $login_url;
    }

    public function filter_logout_url( string $logout_url, string $redirect ): string {
        $blog_id = get_current_blog_id();
        $primary = $this->get_primary_domain( $blog_id );

        if ( ! $primary ) {
            return $logout_url;
        }

        $scheme     = $primary->https ? 'https' : 'http';
        $new_logout = $scheme . '://' . $primary->domain . '/wp-login.php?action=logout';
        $nonce      = wp_create_nonce( 'log-out' );
        $new_logout = add_query_arg( '_wpnonce', $nonce, $new_logout );

        if ( $redirect ) {
            $new_logout = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $new_logout );
        }

        return $new_logout;
    }

    public function validate_token(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sicherheit erfolgt über kryptografisches Einmal-Token.
        if ( empty( $_GET['omdm_token'] ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sicherheit erfolgt über kryptografisches Einmal-Token.
        $token   = isset( $_GET['omdm_token'] ) ? sanitize_text_field( wp_unslash( $_GET['omdm_token'] ) ) : '';
        $user_id = $this->verify_login_token( $token, get_current_blog_id() );

        if ( ! $user_id ) {
            return;
        }

        wp_set_auth_cookie( $user_id, false );
        $this->delete_login_token( $token );

        if ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $redirect = esc_url_raw( rawurldecode( wp_unslash( $_GET['redirect_to'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } else {
            $scheme   = is_ssl() ? 'https' : 'http';
            $host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
            $uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
            $uri      = remove_query_arg( 'omdm_token', $uri );
            $redirect = $scheme . '://' . $host . $uri;
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    private function create_login_token( int $user_id, int $blog_id ): string {
        $table  = $this->db->base_prefix . 'omdm_login_tokens';
        $token  = bin2hex( random_bytes( 32 ) );
        $expiry = gmdate( 'Y-m-d H:i:s', time() + 60 );

        $this->db->insert(
            $table,
            [
                'user_id'    => $user_id,
                'blog_id'    => $blog_id,
                'token'      => $token,
                'expires_at' => $expiry,
            ],
            [ '%d', '%d', '%s', '%s' ]
        );

        return $token;
    }

    private function verify_login_token( string $token, int $blog_id ): ?int {
        $table = $this->db->base_prefix . 'omdm_login_tokens';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, kein Caching für Einmal-Token sinnvoll.
        $row = $this->db->get_row(
            $this->db->prepare(
                'SELECT user_id FROM %i WHERE token = %s AND blog_id = %d AND expires_at > %s LIMIT 1',
                $table,
                $token,
                $blog_id,
                gmdate( 'Y-m-d H:i:s' )
            )
        );

        return $row ? (int) $row->user_id : null;
    }

    private function delete_login_token( string $token ): void {
        $table = $this->db->base_prefix . 'omdm_login_tokens';
        $this->db->delete( $table, [ 'token' => $token ], [ '%s' ] );
    }

    private function get_primary_domain( int $blog_id ): ?object {
        $table = $this->db->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, kein Object-Cache für Domain-Mapping-Tabelle vorhanden.
        return $this->db->get_row(
            $this->db->prepare(
                'SELECT * FROM %i WHERE blog_id = %d AND is_primary = 1 LIMIT 1',
                $table,
                $blog_id
            )
        );
    }
}