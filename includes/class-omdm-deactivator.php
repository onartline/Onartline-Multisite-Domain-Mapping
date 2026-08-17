<?php
/**
 * Plugin Deactivator
 * Räumt bei der Plugin-Deaktivierung auf.
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class omdm_Deactivator {

    /**
     * Wird bei der Plugin-Deaktivierung ausgeführt.
     * Tabellen bleiben erhalten – Daten gehen nicht verloren.
     */
    public static function deactivate(): void {
        self::clear_login_tokens();

        if ( class_exists( 'omdm_Cron_Handler' ) ) {
            omdm_Cron_Handler::unschedule();
        }
    }

    /**
     * Löscht abgelaufene Login-Tokens.
     */
    private static function clear_login_tokens(): void {
        global $wpdb;

        $table_tokens = $wpdb->base_prefix . 'omdm_login_tokens';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aufräum-Query bei Deaktivierung, kein Caching sinnvoll/möglich; $table_tokens besteht ausschließlich aus $wpdb->base_prefix, keine Nutzereingabe.
        $wpdb->query(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabellennamen können nicht als Platzhalter übergeben werden, Wert ist unkritisch (siehe oben).
                "DELETE FROM {$table_tokens} WHERE expires_at < %s",
                current_time( 'mysql' )
            )
        );
    }
}