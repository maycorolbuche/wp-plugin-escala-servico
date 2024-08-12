<?php
// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Outd_List_Table extends WP_List_Table
{
    private $table_data;

    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'post_title' => 'Nome',
            'media' => 'Mídia',
            'outdoor_status' => 'Status',
            'outdoor_order' => 'Ordem',
            'outdoor_start_date' => 'Estreia em',
            'outdoor_end_date' => 'Expira em',
            'outdoor_object_fit' => 'Alinhamento (imagem)',
            'outdoor_duration' => 'Duração (imagem)',
        );

        $options = get_option('outdoor_options');
        if (isset($options["random"]) && $options["random"] == 1){
            unset($columns["outdoor_order"]);
        }
        if (!isset($options["thumb"]) || $options["thumb"] != 1){
            unset($columns["media"]);
        }

        return $columns;
    }

    private function get_table_data($search = '')
    {
        global $wpdb;

        $table = $wpdb->prefix . 'outdoor';
        $table_posts = $wpdb->prefix . 'posts';

        if (isset($_GET["outdoor_status"]) && $_GET["outdoor_status"] != '') {
            $status = sanitize_text_field($_GET["outdoor_status"]);
        } else {
            $status = "active";
        }

        $sql = "SELECT
                    {$table}.outdoor_id,
                    {$table}.post_id,
                    {$table}.outdoor_status,
                    LPAD({$table}.outdoor_order,5,'0') outdoor_order,
                    {$table}.outdoor_start_date,
                    {$table}.outdoor_end_date,
                    {$table}.outdoor_object_fit,
                    {$table}.outdoor_duration,
                    {$table_posts}.post_title,
                    {$table_posts}.post_content,
                    {$table_posts}.post_mime_type,
                    {$table_posts}.guid
                from {$table}
                inner join {$table_posts} on {$table_posts}.id = {$table}.post_id
                WHERE 1=1
                ";

        if ($status != "" && $status != "all") {
            $sql .= " AND {$table}.outdoor_status = '$status'";
        }

        if (!empty($search)) {
            $sql .= " AND {$table_posts}.post_title like '%{$search}%'";
        }

        return $wpdb->get_results(
            $sql,
            ARRAY_A
        );
    }

    public function prepare_items()
    {
        //data
        if (isset($_POST['s'])) {
            $this->table_data = $this->get_table_data(sanitize_text_field($_POST['s']));
        } else {
            $this->table_data = $this->get_table_data();
        }

        $columns = $this->get_columns();
        $hidden = (is_array(get_user_meta(get_current_user_id(), 'managetoplevel_page_outdoorcolumnshidden', true))) ? get_user_meta(get_current_user_id(), 'managetoplevel_page_outdoorcolumnshidden', true) : array();
        $sortable = $this->get_sortable_columns();
        $primary = 'post_title';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);

        usort($this->table_data, array(&$this, 'usort_reorder'));

        /* pagination */
        $per_page = $this->get_items_per_page('elements_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items = count($this->table_data);

        $this->table_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items, // total number of items
            'per_page' => $per_page, // items to show on a page
            'total_pages' => ceil($total_items / $per_page), // use ceil to round up
        ));

        $this->items = $this->table_data;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'media':
                $max_w = 100;
                $max_h = 70;
                $mime = explode('/', $item['post_mime_type']);
                $embed = '';
                $url = $item['guid'];
                $fit = $item['outdoor_object_fit'];
                switch ($mime[0]) {
                    case 'image':
                        $embed = "<img src='{$url}' style='width:{$max_w}px;height:{$max_h}px;object-fit: {$fit};'>";
                        break;
                    default:
                        $embed = "
                            <video muted style='max-width:{$max_w}px;max-height:{$max_h}px;'>
                            <source src='{$url}' type='{$item['post_mime_type']}'>
                        ";
                        break;
                }
                return "<a href='$url' target='_blank'>$embed</a>";
            case 'outdoor_status':
                $cl = '';
                if ($item['outdoor_status'] == 'active') {
                    if ($item['outdoor_start_date'] != ""
                        && $item['outdoor_start_date'] != "0000-00-00"
                        && date("Y-m-d") < $item['outdoor_start_date']) {
                        $st = 'Aguardando';
                        $cl .= 'color:darkblue;';
                    } elseif ($item['outdoor_end_date'] != ""
                        && $item['outdoor_end_date'] != "0000-00-00"
                        && date("Y-m-d") > $item['outdoor_end_date']) {
                        $st = 'Expirado';
                        $cl .= 'color:red;';
                    } else {
                        $st = 'Ativo';
                        $cl .= 'color:green;';
                    }
                } else {
                    $st = 'Inativo';
                }
                return "<span style='$cl'>$st</span>";
                break;
            case 'outdoor_order':
            case 'outdoor_duration':
                $mime = explode('/', $item['post_mime_type']);
                if ($column_name == 'outdoor_duration' && $mime[0] != 'image') {
                    return '';
                }

                $item[$column_name] = intval($item[$column_name]);

                return
                    ' <div id="outd_' . $column_name . '_view_' . $item['outdoor_id'] . '">'
                    . '<a href="javascript:" onclick="outd_visible(\'outd_' . $column_name . '_view_' . $item['outdoor_id'] . '\',\'outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '\')">'
                    . $item[$column_name]
                    . ($column_name == 'outdoor_duration' ? ' seg.' : '')
                    . '</a>'
                    . '</div>'

                    . '<div class="outd_row_fields_edit" style="display:none;" id="outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '">'
                    . '<input min=1 style="width: 60px;" type="number" value="' . $item[$column_name] . '" id="field_' . $column_name . '_' . $item['outdoor_id'] . '">'
                    . '<input onclick="outd_action(\'edit\',' . $item['outdoor_id'] . ',\'' . $column_name . '\',\'field_' . $column_name . '_' . $item['outdoor_id'] . '\');" type="button" class="button button-primary" value="Ok">'
                    . '<input onclick="outd_visible(\'outd_' . $column_name . '_view_' . $item['outdoor_id'] . '\',\'outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '\');" type="button" class="button" value="Cancelar">'
                    . '</div>'
                ;
                break;
            case 'outdoor_start_date':
            case 'outdoor_end_date':
                $none = $item[$column_name] == "" || $item[$column_name] == "0000-00-00";
                $d = explode("-", $item[$column_name]);
                $min = date("Y-m-d");
                if ($column_name == 'outdoor_end_date' && $item['outdoor_start_date'] != '') {
                    $min = $item['outdoor_start_date'];
                }
                $max = "";
                if ($column_name == 'outdoor_start_date' && $item['outdoor_end_date'] != '') {
                    $max = $item['outdoor_end_date'];
                }
                return
                    ' <div id="outd_' . $column_name . '_view_' . $item['outdoor_id'] . '">'
                    . '<a class="' . ($none ? 'row-actions' : '') . '" href="javascript:" onclick="outd_visible(\'outd_' . $column_name . '_view_' . $item['outdoor_id'] . '\',\'outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '\')">'
                    . ($none ? "Definir data" : $d[2] . '/' . $d[1] . '/' . $d[0])
                    . '</a>'
                    . '</div>'

                    . '<div class="outd_row_fields_edit" style="display:none;" id="outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '">'
                    . '<input max="' . $max . '" min="' . $min . '" style="width: 130px;" type="date" value="' . $item[$column_name] . '" id="field_' . $column_name . '_' . $item['outdoor_id'] . '">'
                    . '<input onclick="outd_action(\'edit\',' . $item['outdoor_id'] . ',\'' . $column_name . '\',\'field_' . $column_name . '_' . $item['outdoor_id'] . '\');" type="button" class="button button-primary" value="Ok">'
                    . '<input onclick="outd_visible(\'outd_' . $column_name . '_view_' . $item['outdoor_id'] . '\',\'outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '\');" type="button" class="button" value="Cancelar">'
                    . '</div>'
                ;
                break;
            case 'outdoor_object_fit':
                $none = $item[$column_name] == "";
                $mime = explode('/', $item['post_mime_type']);
                $fit = [
                    'contain' => 'Centralizar',
                    'cover' => 'Ampliar',
                    'fill' => 'Preencher',
                ];
                if ($mime[0] == "image") {
                    $options = "";
                    foreach ($fit as $key => $value) {
                        $options .= "<option value='$key' " . ($key == $item[$column_name] ? "selected" : "") . ">$value</option>";
                    }
                    return
                        ' <div id="outd_' . $column_name . '_view_' . $item['outdoor_id'] . '">'
                        . '<a class="' . ($none ? 'row-actions' : '') . '" href="javascript:" onclick="outd_visible(\'outd_' . $column_name . '_view_' . $item['outdoor_id'] . '\',\'outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '\')">'
                        . $fit[$item[$column_name]]
                        . '</a>'
                        . '</div>'

                        . '<div class="outd_row_fields_edit" style="display:none;" id="outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '">'
                        . '<select style="width: 130px;" id="field_' . $column_name . '_' . $item['outdoor_id'] . '">'
                        . $options
                        . '</select>'
                        . '<input onclick="outd_action(\'edit\',' . $item['outdoor_id'] . ',\'' . $column_name . '\',\'field_' . $column_name . '_' . $item['outdoor_id'] . '\');" type="button" class="button button-primary" value="Ok">'
                        . '<input onclick="outd_visible(\'outd_' . $column_name . '_view_' . $item['outdoor_id'] . '\',\'outd_' . $column_name . '_edit_' . $item['outdoor_id'] . '\');" type="button" class="button" value="Cancelar">'
                        . '</div>'
                    ;
                } else {
                    return "";
                }
                break;
            case 'outdoor_id':
            case 'post_title':
            default:
                return $item[$column_name];
        }
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['outdoor_id']
        );
    }

    protected function get_sortable_columns()
    {
        $sortable_columns = array(
            'post_title' => array('post_title', 'asc'),
            'outdoor_status' => array('outdoor_status', 'asc'),
            'outdoor_order' => array('outdoor_order', 'asc'),
            'outdoor_start_date' => array('outdoor_start_date', 'asc'),
            'outdoor_end_date' => array('outdoor_end_date', 'asc'),
            'outdoor_object_fit' => array('outdoor_object_fit', 'asc'),
            'outdoor_duration' => array('outdoor_duration', 'asc'),
        );
        return $sortable_columns;
    }

    // Sorting function
    public function usort_reorder($a, $b)
    {
        $def_order = 'outdoor_order';
        $options = get_option('outdoor_options');
        if (isset($options["random"]) && $options["random"] == 1){
            $def_order = 'post_title';
        }

        // If no sort, default to user_login
        $orderby = (!empty($_GET['orderby'])) ? sanitize_text_field($_GET['orderby']) : $def_order;

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'asc';

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    // Adding action links to column
    public function column_post_title($item)
    {

        $url = get_admin_url() . 'admin.php';
        $symbol = '?';

        if (isset($_GET["page"])) {
            $url = $url . $symbol . 'page=' . sanitize_text_field($_GET['page']);
            $symbol = '&';
        }
        if (isset($_GET["orderby"])) {
            $url = $url . $symbol . 'orderby=' . sanitize_text_field($_GET['orderby']);
            $symbol = '&';
        }
        if (isset($_GET["order"])) {
            $url = $url . $symbol . 'order=' . sanitize_text_field($_GET['order']);
            $symbol = '&';
        }
        if (isset($_GET["paged"])) {
            $url = $url . $symbol . 'paged=' . sanitize_text_field($_GET['paged']);
            $symbol = '&';
        }
        if (isset($_GET["outdoor_status"]) && $_GET["outdoor_status"] != '') {
            $url = $url . $symbol . 'outdoor_status=' . sanitize_text_field($_GET['outdoor_status']);
            $symbol = '&';
        }

        $url = $url . $symbol;

        $actions = array();
        if ($item['outdoor_status'] == 'active') {
            $actions['status'] = sprintf('<a href="%saction=%s&element=%s">%s</a>', $url, 'inactive', $item['outdoor_id'], 'Desativar');
        } else {
            $actions['status'] = sprintf('<a href="%saction=%s&element=%s">%s</a>', $url, 'active', $item['outdoor_id'], 'Ativar');
        }
        $actions['delete'] = sprintf('<a href="javascript:" onclick="outd_delete(this)" data-href="%saction=%s&element=%s">%s</a>', $url, 'delete', $item['outdoor_id'], 'Apagar');

        return sprintf('%1$s %2$s', '<a href="'.$item['guid'].'" target="_blank">'.$item['post_title'].'</a>', $this->row_actions($actions));
    }

    // To show bulk action dropdown
    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Apagar',
            'Alterar Status' => [
                'active' => 'Ativo',
                'inactive' => 'Inativo',
            ],
        );
        return $actions;
    }

    public function single_row($item)
    {
        echo '<tr class="' . esc_html($item['outdoor_status']) . '" id="outd_row_' . esc_html($item['outdoor_id']) . '">';
        $this->single_row_columns($item);
        echo '</tr>';
    }

    protected function get_table_classes()
    {
        $mode = get_user_setting('posts_list_mode', 'list');

        $mode_class = sanitize_text_field('table-view-' . $mode);

        return array('widefat', 'fixed', 'plugins', 'outdoor-table', $mode_class, $this->_args['plural']);
    }

}
