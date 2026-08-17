<?php
/**
 * Cron Handler Class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class omdm_Cron_Handler {

    private wpdb $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Domain-Check ausführen
     */
    public function run_domain_check(): void {
        $this->cleanup_expired_tokens();
        $this->validate_mapped_domains();
    }

    /**
     * Abgelaufene Login-Tokens löschen
     */
    private function cleanup_expired_tokens(): void {
        $table = $this->db->base_prefix . 'omdm_login_tokens';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Regelmäßige Bereinigung abgelaufener Tokens, kein Caching sinnvoll.
        $this->db->query(
            $this->db->prepare(
                'DELETE FROM %i WHERE expires_at < %s',
                $table,
                gmdate( 'Y-m-d H:i:s' )
            )
        );
    }

    /**
     * Alle gemappten Domains auf Erreichbarkeit prüfen
     *
     * Voraussetzung: Die Tabelle omdm_domain_mapping enthält die Spalte
     * is_reachable (tinyint(1)), in der das Ergebnis der Prüfung
     * gespeichert wird.
     */
    private function validate_mapped_domains(): void {
        $table = $this->db->base_prefix . 'omdm_domain_mapping';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Abfrage aller gemappten Domains zur Erreichbarkeitsprüfung, kein Caching sinnvoll.
        $domains = $this->db->get_results(
            $this->db->prepare( 'SELECT id, domain FROM %i', $table )
        );

        if ( ! $domains ) {
            return;
        }

        foreach ( $domains as $mapping ) {
            $reachable = $this->check_domain_reachable( $mapping->domain );

            $this->db->update(
                $table,
                [ 'is_reachable' => $reachable ? 1 : 0 ],
                [ 'id'           => (int) $mapping->id ],
                [ '%d' ],
                [ '%d' ]
            );
        }
    }

    /**
     * Domain per HTTP-Request prüfen
     */
    private function check_domain_reachable( string $domain ): bool {
        $url      = 'https://' . $domain;
        $response = wp_remote_head( $url, [
            'timeout'     => 10,
            'redirection' => 3,
            'sslverify'   => true,
        ] );

        if ( is_wp_error( $response ) ) {
            $url      = 'http://' . $domain;
            $response = wp_remote_head( $url, [
                'timeout'     => 10,
                'redirection' => 3,
                'sslverify'   => false,
            ] );
        }

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        return $status_code >= 200 && $status_code < 500;
    }

    /**
     * Cron-Job registrieren
     */
    public static function schedule(): void {
        if ( ! wp_next_scheduled( 'omdm_domain_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'omdm_domain_check' );
        }
    }

    /**
     * Cron-Job entfernen
     */
    public static function unschedule(): void {
        $timestamp = wp_next_scheduled( 'omdm_domain_check' );

        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'omdm_domain_check' );
        }
    }
}