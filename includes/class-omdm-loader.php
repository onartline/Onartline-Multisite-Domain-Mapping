<?php
/**
 * Plugin Loader
 * Registriert alle Actions und Filter des Plugins.
 *
 * @package Onartline_Multisite_Domain_Mapping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class omdm_Loader {

    protected array $actions = [];
    protected array $filters = [];
    private array $instances = [];
    private string $plugin_name;
    private string $version;

    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        $this->register_hooks();
    }

    /**
     * Alle Hooks des Plugins registrieren.
     */
    private function register_hooks(): void {

        // Domain Mapper
        $this->add_action( 'init', omdm_Domain_Mapper::class, 'map_domain' );
        $this->add_filter( 'site_url', omdm_Domain_Mapper::class, 'filter_site_url', 10, 4 );
        $this->add_filter( 'home_url', omdm_Domain_Mapper::class, 'filter_home_url', 10, 4 );

        // Login Handler
        $this->add_action( 'wp_login', omdm_Login_Handler::class, 'handle_login', 10, 2 );
        $this->add_action( 'init', omdm_Login_Handler::class, 'validate_token' );
        $this->add_filter( 'login_url', omdm_Login_Handler::class, 'filter_login_url', 10, 3 );
        $this->add_filter( 'logout_url', omdm_Login_Handler::class, 'filter_logout_url', 10, 2 );
        $this->add_filter( 'admin_url', omdm_Login_Handler::class, 'filter_admin_url', 10, 3 );

        // Cron Handler
        $this->add_action( 'omdm_domain_check', omdm_Cron_Handler::class, 'run_domain_check' );

        // Network Admin – lazy, Hook-basiert
        $this->add_action( 'network_admin_menu',                    $this, 'load_network_admin' );
        $this->add_action( 'network_admin_edit_omdm_save_domain',   $this, 'load_network_admin' );
        $this->add_action( 'network_admin_edit_omdm_delete_domain', $this, 'load_network_admin' );
        $this->add_action( 'network_admin_edit_omdm_save_settings', $this, 'load_network_admin' );

        // Activator Notices
        if ( class_exists( 'omdm_Activator' ) ) {
            $this->add_action( 'network_admin_notices', 'omdm_Activator', 'activation_notices' );
        }
    }

    /**
     * Network Admin lazy laden.
     */
    public function load_network_admin(): void {
        static $network_admin = null;

        if ( null === $network_admin ) {
            $network_admin = new omdm_Network_Admin( $this->plugin_name, $this->version );
        }

        match ( current_action() ) {
            'network_admin_menu'                    => $network_admin->add_network_menu(),
            'network_admin_edit_omdm_save_domain'   => $network_admin->save_domain(),
            'network_admin_edit_omdm_delete_domain' => $network_admin->delete_domain(),
            'network_admin_edit_omdm_save_settings' => $network_admin->save_settings(),
            default                                 => null,
        };
    }

    /**
     * Gibt eine bereits instanziierte Komponente zurück oder erzeugt sie bei Bedarf.
     *
     * @param object|string $component Objekt-Instanz oder Klassenname.
     */
    private function resolve_component( object|string $component ): object {
        if ( is_object( $component ) ) {
            return $component;
        }

        if ( ! isset( $this->instances[ $component ] ) ) {
            $this->instances[ $component ] = new $component();
        }

        return $this->instances[ $component ];
    }

    private function add_action(
        string $hook,
        object|string $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ): void {
        $this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
    }

    private function add_filter(
        string $hook,
        object|string $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ): void {
        $this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
    }

    /**
     * Alle registrierten Hooks ausführen.
     */
    public function run(): void {
        foreach ( $this->actions as $action ) {
            add_action(
                $action['hook'],
                function ( ...$args ) use ( $action ) {
                    return call_user_func_array(
                        [ $this->resolve_component( $action['component'] ), $action['callback'] ],
                        $args
                    );
                },
                $action['priority'],
                $action['accepted_args']
            );
        }

        foreach ( $this->filters as $filter ) {
            add_filter(
                $filter['hook'],
                function ( ...$args ) use ( $filter ) {
                    return call_user_func_array(
                        [ $this->resolve_component( $filter['component'] ), $filter['callback'] ],
                        $args
                    );
                },
                $filter['priority'],
                $filter['accepted_args']
            );
        }
    }
}