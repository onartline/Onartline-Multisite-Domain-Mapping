<?php
/**
 * Domain List Table Class
 *
 * @package Onartline_Multisite_Domain_Mapping
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class omdm_Domain_List_Table extends WP_List_Table {


    private wpdb $db;
    private string $table;


    public function __construct() {
        parent::__construct( [
            'singular' => 'domain',
            'plural'   => 'domains',
            'ajax'     => false,
        ] );


        global $wpdb;
        $this->db    = $wpdb;
        $this->table = $wpdb->base_prefix . 'omdm_domain_mapping';
    }


    public function get_columns(): array {
        return [
            'cb'         => '<input type="checkbox">',
            'domain'     => __( 'Domain', 'onartline-multisite-domain-mapping' ),
            'blog_id'    => __( 'Site', 'onartline-multisite-domain-mapping' ),
            'is_primary' => __( 'Primary', 'onartline-multisite-domain-mapping' ),
            'https'      => __( 'HTTPS', 'onartline-multisite-domain-mapping' ),
        ];
    }


    public function get_sortable_columns(): array {
        return [
            'domain'  => [ 'domain', false ],
            'blog_id' => [ 'blog_id', true ],
        ];
    }


    public function get_bulk_actions(): array {
        return [
            'bulk_delete' => __( 'Delete', 'onartline-multisite-domain-mapping' ),
        ];
    }


    public function get_views(): array {
        $total     = $this->get_count();
        $primary   = $this->get_count( 'primary' );
        $secondary = $this->get_count( 'secondary' );


        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rein lesender Filterparameter zur Ansichtssteuerung, keine Datenänderung.
        $current  = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : 'all';
        $base_url = network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping' );


        return [
            'all' => sprintf(
                '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
                esc_url( $base_url ),
                $current === 'all' ? 'class="current"' : '',
                esc_html__( 'All', 'onartline-multisite-domain-mapping' ),
                $total
            ),
            'primary' => sprintf(
                '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'filter', 'primary', $base_url ) ),
                $current === 'primary' ? 'class="current"' : '',
                esc_html__( 'Primary', 'onartline-multisite-domain-mapping' ),
                $primary
            ),
            'secondary' => sprintf(
                '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'filter', 'secondary', $base_url ) ),
                $current === 'secondary' ? 'class="current"' : '',
                esc_html__( 'Secondary', 'onartline-multisite-domain-mapping' ),
                $secondary
            ),
        ];
    }


    private function get_count( string $filter = 'all' ): int {
        if ( $filter === 'primary' ) {
            return (int) $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->table} WHERE is_primary = 1"
            );
        }


        if ( $filter === 'secondary' ) {
            return (int) $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->table} WHERE is_primary = 0"
            );
        }


        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->table}"
        );
    }


    public function prepare_items(): void {
        $per_page     = $this->get_items_per_page( 'omdm_domains_per_page', 20 );
        $current_page = $this->get_pagenum();


        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rein lesender Filterparameter zur Ansichtssteuerung, keine Datenänderung.
        $filter       = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : 'all';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rein lesender Suchparameter, keine Datenänderung.
        $search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';


        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rein lesender Sortierparameter, keine Datenänderung.
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'blog_id';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rein lesender Sortierparameter, keine Datenänderung.
        $order   = isset( $_GET['order'] ) && strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) === 'DESC' ? 'DESC' : 'ASC';


        $allowed_orderby = [ 'domain', 'blog_id' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'blog_id';
        }


        $where = [];
        $args  = [];


        if ( $filter === 'primary' ) {
            $where[] = 'is_primary = 1';
        } elseif ( $filter === 'secondary' ) {
            $where[] = 'is_primary = 0';
        }


        if ( $search ) {
            $where[] = 'domain LIKE %s';
            $args[]  = '%' . $this->db->esc_like( $search ) . '%';
        }


        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';


        $total_items = (int) $this->db->get_var(
            $args
                ? $this->db->prepare( "SELECT COUNT(*) FROM {$this->table} {$where_sql}", ...$args )
                : "SELECT COUNT(*) FROM {$this->table} {$where_sql}"
        );


        $offset   = ( $current_page - 1 ) * $per_page;
        $query    = "SELECT * FROM {$this->table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $args[]   = $per_page;
        $args[]   = $offset;


        $this->items = $this->db->get_results(
            $this->db->prepare( $query, ...$args )
        );


        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );


        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
    }


    public function column_cb( $item ): string {
        return sprintf(
            '<input type="checkbox" name="domain_ids[]" value="%d">',
            (int) $item->id
        );
    }


    public function column_domain( $item ): string {
        $edit_url   = network_admin_url( 'admin.php?page=onartline-multisite-domain-mapping-add&edit=' . $item->id );
        $delete_url = wp_nonce_url(
            network_admin_url( 'edit.php?action=omdm_delete_domain&id=' . $item->id ),
            'omdm_delete_domain_' . $item->id
        );


        $actions = [
            'edit'   => sprintf(
                '<a href="%s">%s</a>',
                esc_url( $edit_url ),
                esc_html__( 'Edit', 'onartline-multisite-domain-mapping' )
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
                esc_url( $delete_url ),
                esc_js( __( 'Really delete this domain?', 'onartline-multisite-domain-mapping' ) ),
                esc_html__( 'Delete', 'onartline-multisite-domain-mapping' )
            ),
        ];


        return sprintf(
            '<strong>%s</strong> %s',
            esc_html( $item->domain ),
            $this->row_actions( $actions )
        );
    }


    public function column_blog_id( $item ): string {
        $site = get_blog_details( (int) $item->blog_id );
        return esc_html( $site
            ? $site->blogname . ' (ID: ' . $item->blog_id . ')'
            : '– (ID: ' . $item->blog_id . ')' );
    }


    public function column_is_primary( $item ): string {
        return $item->is_primary ? '✔' : '–';
    }


    public function column_https( $item ): string {
        return $item->https ? '✔' : '–';
    }


    public function column_default( $item, $column_name ): string {
        return esc_html( $item->$column_name ?? '–' );
    }


    public function no_items(): void {
        esc_html_e( 'No domains configured.', 'onartline-multisite-domain-mapping' );
    }
}