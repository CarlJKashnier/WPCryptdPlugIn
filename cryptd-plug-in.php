<?php

/*
Plugin Name: crypteds
Description: A breif plugin to manage custom table data. Also dispay it on the front end
Version: 1.0
Author: Carl Kashnier
License: None
*/

function create_the_custom_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'crypteds';

    $sql = "CREATE TABLE " . $table_name . " (
`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
`name` varchar(256) NOT NULL,
`description` varchar(256) NOT NULL,
`image` varchar(256) NOT NULL
) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_the_custom_table');

function uninstall_crypteds_plugin()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'crypteds';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'uninstall_crypteds_plugin');

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class Crypteds_Custom_Table_List_Table extends WP_List_Table
{

    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'person',
            'plural' => 'persons',
        ));
    }

    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    function column_age($item)
    {
        return '<em>' . $item['age'] . '</em>';
    }

    function column_ip($item)
    {
        return '<em>' . $item['ip_address'] . '</em>';
    }

    function column_name($item)
    {
        $actions = array(
            'edit' => sprintf(
                '<a href="?page=crypteds_form&id=%s">%s</a>',
                $item['id'],
                __('Edit', 'crypteds_custom_table')
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s">%s</a>',
                $_REQUEST['page'],
                $item['id'],
                __('Delete', 'crypteds_custom_table')
            ),
        );

        return sprintf(
            '%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => __('Name', 'crypteds_custom_table'),
            'description' => __('Description', 'crypteds_custom_table'),
            'image' => __('Image', 'crypteds_custom_table'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'crypteds'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'crypteds';

        $per_page = 5;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array(
                $_REQUEST['orderby'],
                array_keys($this->get_sortable_columns())
            )) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc')
            )) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged),
            ARRAY_A
        );

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

}


function crypted_custom_table_admin_menu()
{
    add_menu_page(
        __('Crypted', 'crypteds_custom_table'),
        __('Crypteds', 'crypteds_custom_table'),
        'activate_plugins',
        'crypteds',
        'crypteds_custom_table_page_handler',
        'dashicons-welcome-learn-more',
        '5'
    );
}
add_action('admin_menu', 'crypted_custom_table_admin_menu');

function crypteds_custom_table_page_handler()
{
    global $wpdb;

    $table = new Crypteds_Custom_Table_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(
                __('Items deleted: %d', 'crypteds_custom_table'),
                count($_REQUEST['id'])
            ) . '</p></div>';
    }
}
?>



