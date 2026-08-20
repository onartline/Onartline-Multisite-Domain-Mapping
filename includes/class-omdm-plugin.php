<?php
/**
 * Main plugin class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class omdm_Plugin {


    protected omdm_Loader $loader;
    protected string $plugin_name = 'onartline-multisite-domain-mapping';
    protected string $version = '1.0.1';


    public function __construct() {
        $this->load_dependencies();
    }


    private function load_dependencies(): void {
        require_once omdm_PLUGIN_DIR . 'includes/class-omdm-loader.php';
        require_once omdm_PLUGIN_DIR . 'includes/class-omdm-activator.php';
        require_once omdm_PLUGIN_DIR . 'includes/class-omdm-deactivator.php';
        require_once omdm_PLUGIN_DIR . 'includes/class-omdm-domain-mapper.php';
        require_once omdm_PLUGIN_DIR . 'includes/class-omdm-login-handler.php';
        require_once omdm_PLUGIN_DIR . 'includes/class-omdm-cron-handler.php';
        require_once omdm_PLUGIN_DIR . 'admin/class-omdm-network-admin.php';


        $this->loader = new omdm_Loader( $this->plugin_name, $this->version );
    }


    public function run(): void {
        $this->loader->run();
    }


    public function get_plugin_name(): string {
        return $this->plugin_name;
    }


    public function get_version(): string {
        return $this->version;
    }
}