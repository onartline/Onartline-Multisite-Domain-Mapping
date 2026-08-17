<?php
/**
 * Sunrise – Domain Mapping Bootstrap
 *
 * Diese Datei wird von WordPress sehr früh geladen (vor mu-plugins).
 * Sie ist verantwortlich für das eigentliche Domain Mapping im Frontend.
 *
 * WICHTIG: In der wp-config.php muss folgendes definiert sein:
 * define( 'SUNRISE', true );
 *
 * omdm_SUNRISE_MARKER – Detection line for Onartline Multisite Domain Mapping, please do not remove!
 * omdm_SUNRISE_VERSION: 1.0.2
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Nur in Multisite ausführen
if ( ! defined( 'MULTISITE' ) || ! MULTISITE ) {
    return;
}

global $wpdb;

// Sicherstellen dass wpdb und base_prefix bereit sind
if ( empty( $wpdb->base_prefix ) ) {
    return;
}

/**
 * Schlanke Sanitisierung ohne sanitize_text_field().
 *
 * sanitize_text_field() ruft intern wp_check_invalid_utf8() auf, welche
 * is_utf8_charset() und damit get_option( 'blog_charset' ) aufruft. An dieser
 * Stelle im Bootstrap ist $wpdb->set_blog_id() jedoch noch nicht erfolgt, d. h.
 * $wpdb->prefix bzw. $wpdb->options ist noch leer. Das führt zu einer
 * fehlerhaften SQL-Query ("FROM" ohne Tabellenname). Daher wird hier bewusst
 * auf eine einfache, DB-unabhängige Bereinigung zurückgegriffen.
 */
function omdm_sunrise_sanitize( string $value ): string {
    // Steuerzeichen und Nullbytes entfernen, kein DB-Zugriff notwendig.
    return preg_replace( '/[\x00-\x1F\x7F]+/', '', $value );
}

// HTTP_HOST sicher auslesen
$omdm_current_domain = isset( $_SERVER['HTTP_HOST'] )
    ? strtolower( omdm_sunrise_sanitize( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Wird bewusst über omdm_sunrise_sanitize() statt sanitize_text_field() bereinigt, siehe Docblock oben.
    : '';

if ( ! $omdm_current_domain ) {
    return;
}

// Port entfernen falls vorhanden
$omdm_current_domain = preg_replace( '/:\d+$/', '', $omdm_current_domain );

// Domain validieren
if ( ! preg_match( '/^[a-z0-9.\-]+$/', $omdm_current_domain ) ) {
    return;
}

// Domain-Mapping aus DB holen
$omdm_table = $wpdb->base_prefix . 'omdm_domain_mapping';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
$omdm_mapping = $wpdb->get_row(
    $wpdb->prepare(
        'SELECT * FROM %i WHERE domain = %s LIMIT 1',
        $omdm_table,
        $omdm_current_domain
    )
);

if ( ! $omdm_mapping ) {
    return;
}

// Aktuelles Protokoll ermitteln
$omdm_is_https = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' );

// Globale Einstellungen einmalig laden
$omdm_option_table = $wpdb->base_prefix . 'sitemeta';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
$omdm_redirect_enabled = (bool) $wpdb->get_var(
    $wpdb->prepare(
        'SELECT meta_value FROM %i WHERE meta_key = %s AND site_id = %d LIMIT 1',
        $omdm_option_table,
        'omdm_301_redirect',
        1
    )
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
$omdm_https_global = (bool) $wpdb->get_var(
    $wpdb->prepare(
        'SELECT meta_value FROM %i WHERE meta_key = %s AND site_id = %d LIMIT 1',
        $omdm_option_table,
        'omdm_force_https',
        1
    )
);

// --- Primary-Domain-Redirect (inkl. HTTPS in einem Schritt) ---
if ( $omdm_redirect_enabled && ! $omdm_mapping->is_primary ) {

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
    $omdm_primary = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT domain, https FROM %i WHERE blog_id = %d AND is_primary = 1 LIMIT 1',
            $omdm_table,
            (int) $omdm_mapping->blog_id
        )
    );

    if ( $omdm_primary ) {
        $omdm_target_force_https = $omdm_primary->https || $omdm_https_global;
        $omdm_protocol            = $omdm_target_force_https || $omdm_is_https ? 'https' : 'http';

        $omdm_request_uri = isset( $_SERVER['REQUEST_URI'] )
            ? omdm_sunrise_sanitize( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Wird bewusst über omdm_sunrise_sanitize() statt sanitize_text_field() bereinigt, siehe Docblock oben.
            : '/';

        $omdm_redirect = $omdm_protocol . '://' . $omdm_primary->domain . $omdm_request_uri;

        header( 'HTTP/1.1 301 Moved Permanently' );
        header( 'Location: ' . $omdm_redirect );
        exit;
    }
}

// --- HTTPS-Weiterleitung (nur relevant, wenn kein Primary-Redirect erfolgt ist) ---
$omdm_force_https = $omdm_mapping->https || $omdm_https_global;

if ( $omdm_force_https && ! $omdm_is_https ) {
    $omdm_request_uri = isset( $_SERVER['REQUEST_URI'] )
        ? omdm_sunrise_sanitize( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Wird bewusst über omdm_sunrise_sanitize() statt sanitize_text_field() bereinigt, siehe Docblock oben.
        : '/';

    $omdm_redirect = 'https://' . $omdm_current_domain . $omdm_request_uri;
    header( 'HTTP/1.1 301 Moved Permanently' );
    header( 'Location: ' . $omdm_redirect );
    exit;
}
// Blog-Details direkt aus DB laden
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
$omdm_blog = $wpdb->get_row(
    $wpdb->prepare(
        'SELECT * FROM %i WHERE blog_id = %d LIMIT 1',
        $wpdb->blogs,
        (int) $omdm_mapping->blog_id
    )
);

if ( ! $omdm_blog ) {
    return;
}

$blog_id      = (int) $omdm_blog->blog_id;
$omdm_site_id = (int) $omdm_blog->site_id;

// Network/Site direkt aus DB laden
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
$current_site = $wpdb->get_row(
    $wpdb->prepare(
        'SELECT * FROM %i WHERE id = %d LIMIT 1',
        $wpdb->site,
        $omdm_site_id
    )
);

// Globale WordPress-Variablen setzen
$current_blog         = $omdm_blog;
$current_blog->domain = $omdm_current_domain;

// Bei aktivem Domain-Mapping wird die Domain immer als eigener Root behandelt,
// unabhängig davon ob es sich um eine Subdomain- oder Verzeichnis-Multisite handelt.
$current_blog->path = '/';

// Haupt-Blog-ID des Netzwerks dynamisch ermitteln (nicht zwingend 1)
$omdm_main_blog_id = 1;

if ( $current_site ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direkte Query notwendig, da der Objekt-Cache an dieser Stelle im Bootstrap noch nicht verfügbar ist.
    $omdm_main_blog_id_result = $wpdb->get_var(
        $wpdb->prepare(
            'SELECT blog_id FROM %i WHERE site_id = %d AND domain = %s AND path = %s LIMIT 1',
            $wpdb->blogs,
            $omdm_site_id,
            $current_site->domain,
            $current_site->path
        )
    );

    if ( $omdm_main_blog_id_result ) {
        $omdm_main_blog_id = (int) $omdm_main_blog_id_result;
    }
}

// Konstanten definieren
if ( ! defined( 'BLOG_ID_CURRENT_SITE' ) ) {
    define( 'BLOG_ID_CURRENT_SITE', $omdm_main_blog_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required WordPress core multisite constant, cannot be prefixed.
}

if ( ! defined( 'SITE_ID_CURRENT_SITE' ) ) {
    define( 'SITE_ID_CURRENT_SITE', $omdm_site_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required WordPress core multisite constant, cannot be prefixed.
}

// Echte Netzwerk-Domain aus wp_site verwenden, nicht die gemappte Domain
if ( ! defined( 'DOMAIN_CURRENT_SITE' ) ) {
    define( 'DOMAIN_CURRENT_SITE', $current_site ? $current_site->domain : $omdm_current_domain ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required WordPress core multisite constant, cannot be prefixed.
}

// Echten Netzwerk-Pfad aus wp_site verwenden, nicht fest auf '/' setzen
if ( ! defined( 'PATH_CURRENT_SITE' ) ) {
    define( 'PATH_CURRENT_SITE', $current_site ? $current_site->path : '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required WordPress core multisite constant, cannot be prefixed.
}

// Cookie-Domain auf gemappte Domain setzen
if ( ! defined( 'COOKIE_DOMAIN' ) ) {
    define( 'COOKIE_DOMAIN', $omdm_current_domain );
}

// COOKIEPATH, SITECOOKIEPATH und ADMIN_COOKIE_PATH werden bewusst nicht mehr
// hier hartkodiert. WordPress-Core (wp_cookie_constants()) berechnet diese Werte
// korrekt selbst anhand von PATH_CURRENT_SITE und berücksichtigt dabei auch einen
// individuell angepassten Admin-Pfad, z. B. durch Security-Plugins wie Kadence
// Security ("Backend verstecken"-Funktion).