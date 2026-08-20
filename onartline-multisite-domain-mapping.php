<?php
/**
 * Plugin Name:       Onartline Multisite Domain Mapping
 * Plugin URI:        https://wordpress.org/plugins/onartline-multisite-domain-mapping
 * Description:       Map domains to sites in a WordPress Multisite network. Requires PHP 8.3+ and WordPress 7.0+.
 * Version:           1.0.1
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            onartline
 * Author URI:        https://onartline.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       onartline-multisite-domain-mapping
 * Domain Path:       /languages
 * Network:           true
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Plugin-Konstanten ─────────────────────────────────────────────────────────

define( 'omdm_VERSION',     '1.0.1' );
define( 'omdm_DB_VERSION',  '1.0.0' );
define( 'omdm_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'omdm_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'omdm_PLUGIN_FILE', __FILE__ );

// ── Mindestanforderungen prüfen ───────────────────────────────────────────────
// Hinweis: Die Multisite-Prüfung erfolgt separat über
// omdm_Activator::maybe_deactivate_self(), damit keine doppelte
// Hinweismeldung entsteht.

function omdm_check_requirements(): bool {

    if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html__(
                    'Onartline Multisite Domain Mapping requires PHP 8.3 or higher.',
                    'onartline-multisite-domain-mapping'
                )
                . '</p></div>';
        } );
        return false;
    }

    return true;
}

// ── Abhängigkeiten laden ──────────────────────────────────────────────────────

function omdm_load_dependencies(): void {
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-activator.php';
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-deactivator.php';
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-loader.php';
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-domain-mapper.php';
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-login-handler.php';
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-cron-handler.php';
    require_once omdm_PLUGIN_DIR . 'includes/class-omdm-site-settings.php';
    require_once omdm_PLUGIN_DIR . 'admin/class-omdm-network-admin.php';
    require_once omdm_PLUGIN_DIR . 'admin/class-omdm-domain-list-table.php';
}

// Sofort laden – nicht erst bei plugins_loaded
omdm_load_dependencies();

// ── Aktivierungs- und Deaktivierungs-Hooks ────────────────────────────────────

register_activation_hook(
    omdm_PLUGIN_FILE,
    [ 'omdm_Activator', 'activate' ]
);

register_deactivation_hook(
    omdm_PLUGIN_FILE,
    [ 'omdm_Deactivator', 'deactivate' ]
);

// ── Datenbank-Tabellen prüfen und ggf. anlegen (Fallback) ────────────────────
// Fängt Fälle ab wo register_activation_hook bei Netzwerk-Aktivierung
// nicht zuverlässig gefeuert wird.

add_action( 'admin_init', function () {

    if ( ! is_multisite() ) {
        return;
    }

    if ( get_site_option( 'omdm_db_version' ) !== omdm_DB_VERSION ) {
        omdm_Activator::activate();
        update_site_option( 'omdm_db_version', omdm_DB_VERSION );
    }

} );

// ── Plugin automatisch deaktivieren, falls es auf einer Single-Site-Installation
//    aktiviert wurde (WordPress trägt es nach dem Aktivierungs-Hook sonst
//    automatisch wieder als aktiv ein) ─────────────────────────────────────────

add_action( 'admin_init', [ 'omdm_Activator', 'maybe_deactivate_self' ] );

// ── Admin-Notices nach Aktivierung ────────────────────────────────────────────

add_action( 'admin_notices', [ 'omdm_Activator', 'activation_notices' ] );
add_action( 'network_admin_notices', [ 'omdm_Activator', 'activation_notices' ] );

// ── Button "Installation jetzt fortsetzen" verarbeiten ────────────────────────

add_action( 'admin_post_omdm_retry_sunrise', [ 'omdm_Activator', 'retry_sunrise_install' ] );

// ── Plugin initialisieren ─────────────────────────────────────────────────────

function omdm_init(): void {

    if ( ! omdm_check_requirements() ) {
        return;
    }

    // Site-Settings nur laden wenn vom Super-Admin erlaubt
    if ( is_admin() && ! is_network_admin() && get_site_option( 'omdm_allow_site_mapping', false ) ) {
        $site_settings = new omdm_Site_Settings();
        $site_settings->init();
    }

    $loader = new omdm_Loader( 'onartline-multisite-domain-mapping', omdm_VERSION );
    $loader->run();
}

// ── Plugin starten ────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'omdm_init' );