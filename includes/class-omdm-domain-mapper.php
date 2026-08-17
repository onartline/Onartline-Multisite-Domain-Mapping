<?php
/**
 * Domain Mapper Class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class omdm_Domain_Mapper {

    private wpdb $db;

    /**
     * Cache-Gruppe für Domain-Mapping-Abfragen.
     */
    private const CACHE_GROUP = 'omdm_domain_mapping';

    /**
     * Cache-Dauer in Sekunden (1 Stunde).
     */
    private const CACHE_TTL = HOUR_IN_SECONDS;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Domain dem richtigen Site zuordnen
     */
    public function map_domain(): void {
        if ( is_admin() ) {
            return;
        }

        $current_domain = $this->get_current_domain();
        $mapping        = $this->get_mapping_by_domain( $current_domain );

        if ( ! $mapping ) {
            return;
        }

        $this->maybe_redirect_to_primary( $mapping );
        $this->maybe_redirect_to_https( $mapping );
    }

    /**
     * site_url filtern
     */
    public function filter_site_url( string $url, string $path, ?string $scheme, ?int $blog_id ): string {
        if ( ! $blog_id ) {
            $blog_id = get_current_blog_id();
        }

        $primary = $this->get_primary_domain( $blog_id );

        if ( ! $primary ) {
            return $url;
        }

        return $this->replace_domain_in_url( $url, $primary, $scheme );
    }

    /**
     * home_url filtern
     */
    public function filter_home_url( string $url, string $path, ?string $scheme, ?int $blog_id ): string {
        if ( ! $blog_id ) {
            $blog_id = get_current_blog_id();
        }

        $primary = $this->get_primary_domain( $blog_id );

        if ( ! $primary ) {
            return $url;
        }

        return $this->replace_domain_in_url( $url, $primary, $scheme );
    }

    /**
     * Aktuelle Domain ermitteln
     */
    private function get_current_domain(): string {
        $host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
        return strtolower( $host );
    }

    /**
     * Mapping anhand Domain aus DB holen (mit Object-Cache).
     */
    private function get_mapping_by_domain( string $domain ): ?object {
        $cache_key = 'domain_' . md5( $domain );
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

        if ( $found ) {
            return $cached ?: null;
        }

        $table = $this->db->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Ergebnis wird im Object-Cache gehalten, daher kein zusätzliches Caching-Tag nötig.
        $mapping = $this->db->get_row(
            $this->db->prepare(
                'SELECT * FROM %i WHERE domain = %s LIMIT 1',
                $table,
                $domain
            )
        );

        wp_cache_set( $cache_key, $mapping, self::CACHE_GROUP, self::CACHE_TTL );

        return $mapping;
    }

    /**
     * Primary Domain eines Sites holen (mit Object-Cache).
     */
    private function get_primary_domain( int $blog_id ): ?object {
        $cache_key = 'primary_' . $blog_id;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

        if ( $found ) {
            return $cached ?: null;
        }

        $table = $this->db->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Ergebnis wird im Object-Cache gehalten, daher kein zusätzliches Caching-Tag nötig.
        $primary = $this->db->get_row(
            $this->db->prepare(
                'SELECT * FROM %i WHERE blog_id = %d AND is_primary = 1 LIMIT 1',
                $table,
                $blog_id
            )
        );

        wp_cache_set( $cache_key, $primary, self::CACHE_GROUP, self::CACHE_TTL );

        return $primary;
    }

    /**
     * Cache für eine bestimmte Domain und/oder einen Blog invalidieren.
     * Wird von class-wsdm-network-admin.php nach save/delete aufgerufen.
     */
    public static function invalidate_cache( ?string $domain = null, ?int $blog_id = null ): void {
        if ( $domain ) {
            wp_cache_delete( 'domain_' . md5( $domain ), self::CACHE_GROUP );
        }

        if ( $blog_id ) {
            wp_cache_delete( 'primary_' . $blog_id, self::CACHE_GROUP );
        }
    }

    /**
     * Auf Primary Domain weiterleiten falls nötig
     */
    private function maybe_redirect_to_primary( object $mapping ): void {
        if ( $mapping->is_primary ) {
            return;
        }

        $redirect_enabled = get_site_option( 'omdm_301_redirect', false );

        if ( ! $redirect_enabled ) {
            return;
        }

        $primary = $this->get_primary_domain( (int) $mapping->blog_id );

        if ( ! $primary ) {
            return;
        }

        $current_domain = $this->get_current_domain();
        if ( $current_domain === $primary->domain ) {
            return;
        }

        $scheme      = $primary->https ? 'https' : 'http';
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
        $target      = $scheme . '://' . $primary->domain . $request_uri;

        wp_redirect( esc_url_raw( $target ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- gezielte Weiterleitung auf gemappte Domain.
        exit;
    }

    /**
     * Auf HTTPS weiterleiten falls nötig
     */
    private function maybe_redirect_to_https( object $mapping ): void {
        $force_https_global = get_site_option( 'omdm_force_https', false );
        $force_https_domain = (bool) $mapping->https;

        if ( ! $force_https_global && ! $force_https_domain ) {
            return;
        }

        $https_header    = isset( $_SERVER['HTTPS'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) : '';
        $forwarded_proto = isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) : '';

        $is_https = ( 'on' === $https_header )
            || ( 'https' === $forwarded_proto )
            || ( isset( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] );

        if ( $is_https ) {
            return;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
        $target      = 'https://' . $mapping->domain . $request_uri;

        wp_redirect( esc_url_raw( $target ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- gezielte Weiterleitung auf gemappte Domain.
        exit;
    }

    /**
     * Domain in URL ersetzen
     */
    private function replace_domain_in_url( string $url, object $primary, ?string $scheme ): string {
        $parsed = wp_parse_url( $url );

        if ( ! isset( $parsed['host'] ) ) {
            return $url;
        }

        $new_scheme = $scheme ?? $parsed['scheme'] ?? 'https';

        if ( $primary->https || get_site_option( 'omdm_force_https', false ) ) {
            $new_scheme = 'https';
        }

        $new_url  = $new_scheme . '://' . $primary->domain;
        $new_url .= $parsed['path'] ?? '';

        if ( ! empty( $parsed['query'] ) ) {
            $new_url .= '?' . $parsed['query'];
        }

        if ( ! empty( $parsed['fragment'] ) ) {
            $new_url .= '#' . $parsed['fragment'];
        }

        return $new_url;
    }
}