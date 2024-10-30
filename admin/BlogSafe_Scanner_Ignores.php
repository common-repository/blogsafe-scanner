<?php

if (!defined('WPINC')) {
    die;
}
class BSScanner_Ignores_Table extends WP_List_Table {
    var $example_data = array();
    function __construct() {
        global $status, $page;
        parent::__construct(array(
            'singular' => __('Ignored file', 'mylisttable'), //singular name of the listed records
            'plural' => __('Ignored files', 'mylisttable'), //plural name of the listed records
            'ajax' => false //does this table support ajax?
        ));
    }
    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'ID':
                return $item[$column_name];
            case 'fileName':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }
    function column_ID($item) {
        $nonce = wp_create_nonce('BSSnonce');
        $actions = array(
            'removeignore' => sprintf('<a href="?page=%s&action=%s&ID=%s&BSSnonce=' . $nonce . '">' . __('Remove Ignore', 'BSScanner') . '</a>', sanitize_text_field($_REQUEST['page']), 'removeignore', $item['ID']),
        );
        return sprintf('%1$s %2$s', $item['ID'], $this->row_actions($actions));
    }
    function get_bulk_actions() {
        $actions = array(
            'removeignore' => __('Remove Ignore', 'BSScanner')
        );
        return $actions;
    }
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="ID[]" value="%s" />', $item['ID']);
    }
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'ID' => __('ID', 'mylisttable'),
            'fileName' => __('File Name', 'mylisttable')
        );
        return $columns;
    }
    function get_sortable_columns() {
        $sortable_columns = array(
            'fileName' => array(
                'fileName',
                false
            )
        );
        return $sortable_columns;
    }
    function get_items($column = 'fileName', $order = 'DESC') {
        global $wpdb;
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        switch ($column) {
            case 'fileName':
                break;
            default :
                $column = 'fileName';
        }
        switch ($order) {
            case 'ASC':
                break;
            case 'DESC';
                break;
            default :
                $order = 'DESC';
        }
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $SQL = $wpdb->prepare("SELECT * FROM $table_name where fileName LIKE '%%%s%%' and ignoredFile = 1 order by %s %s", sanitize_text_field($_GET['s']), $column, $order);
        } else {
            delete_option('BSScanner_lastSearch');
            $SQL = $wpdb->prepare("SELECT ID, fileName FROM $table_name where ignoredFile = 1 order by %s %s", $column , $order);
        }
        $mylink = $wpdb->get_results($SQL);
        return $mylink;
    }
    //replacement function removes _wp_nonce and _wp_http_referer
    function display_tablenav($which) {
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">

            <div class="alignleft actions">
        <?php $this->bulk_actions(); ?>
            </div>
        <?php
        $this->extra_tablenav($which);
        $this->pagination($which);
        ?>
            <br class="clear" />
        </div>
        <?php
    }
    function prepare_items() {

        if (isset($_GET['orderby']) && isset($_GET['order'])) {
            $orderby = sanitize_text_field($_GET['orderby']);
            $order = sanitize_text_field($_GET['order']);
        } else {
            $orderby = 'fileName';
            $order = 'desc';
        }
        $mylink = $this->get_items($orderby, $order);
        $example_data = array();
        foreach ($mylink as $link) {
            $example_data[] = array(
                'ID' => $link->ID,
                'fileName' => $link->fileName
            );
        }
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array(
            $columns,
            $hidden,
            $sortable
        );
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = count($example_data);
        // only ncessary because we have sample data
        if ($total_items > 0) {
            $example_data = array_slice($example_data, (($current_page - 1) * $per_page), $per_page);
        }
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ));
        $this->items = $example_data;
    }
}
//class


function BSScanner_Render_Ignores_Page() {

    $myListTable = new BSScanner_Ignores_Table();
    $myListTable->prepare_items();

    $nonce = wp_create_nonce('BSSnonce');
    echo '<div id="col-left">';
    echo '<div class="col-wrap">';

    echo '</pre><div class="wrap"><h2>' . __('Ignored Files', 'BSScanner') . '</h2>';
    echo '<p>' . __('Files currently being ignored by BlogSafe Scanner', 'BSScanner') . '</p><hr>';
    echo '</div>';
    echo '</div>';
    echo '</div><!-- /col-left -->';
    echo '<div id="col-right">';
    echo '<div class="col-wrap">';
    echo '<p>' . __('Whenever BlogSafe Scanner detects that one of the following files has been modified, it ignores it and does not report it as being modified.', 'BSScanner') . '</p>';
    echo '<form id="BSSIgnored" method="get" style="padding-right: 5px; margin-right: 10px;">';
    echo '<input type="hidden" name="page" value="' . sanitize_text_field($_REQUEST['page'])
            . '" />' . $myListTable->search_box('search', 'search_id');
    $myListTable->display();
    echo '<input name="BSSnonce" type="hidden" value="' . $nonce . '" />';
    echo '<input type="hidden" name="action" value="removeignore" />';
    echo '</form>';

    echo '</div></div>';
}
?>