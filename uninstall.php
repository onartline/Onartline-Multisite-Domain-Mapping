<?php
/**
 * Uninstall Script
 *
 * Wird ausgeführt, wenn das Plugin über die Plugins-Übersicht gelöscht wird.
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! is_multisite() ) {
    return;
}

global $wpdb;

// ── sunrise.php entfernen – NUR wenn sie von diesem Plugin stammt ──────────
$omdm_sunrise_path = WP_CONTENT_DIR . '/sunrise.php';

if ( file_exists( $omdm_sunrise_path ) ) {
    $omdm_sunrise_content = file_get_contents( $omdm_sunrise_path );

    if ( $omdm_sunrise_content !== false && strpos( $omdm_sunrise_content, 'omdm_SUNRISE_MARKER' ) !== false ) {
        wp_delete_file( $omdm_sunrise_path );
    }
}

// ── Datenbank-Tabellen nur löschen, wenn der Nutzer dies aktiviert hat ─────
if ( get_site_option( 'omdm_delete_data_on_uninstall' ) ) {
    $omdm_prefix = $wpdb->base_prefix;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall-Routine, DDL-Statement, Caching nicht relevant; $omdm_prefix stammt aus $wpdb->base_prefix, kein Nutzer-Input, prepare() unterstützt keine Tabellennamen.
    $wpdb->query( "DROP TABLE IF EXISTS {$omdm_prefix}omdm_domain_mapping" );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall-Routine, DDL-Statement, Caching nicht relevant; $omdm_prefix stammt aus $wpdb->base_prefix, kein Nutzer-Input, prepare() unterstützt keine Tabellennamen.
    $wpdb->query( "DROP TABLE IF EXISTS {$omdm_prefix}omdm_login_tokens" );
}

// ── Site-Options immer entfernen ────────────────────────────────────────────
delete_site_option( 'omdm_version' );
delete_site_option( 'omdm_force_https' );
delete_site_option( 'omdm_301_redirect' );
delete_site_option( 'omdm_allow_site_mapping' );
delete_site_option( 'omdm_server_ip' );
delete_site_option( 'omdm_server_cname' );
delete_site_option( 'omdm_delete_data_on_uninstall' );