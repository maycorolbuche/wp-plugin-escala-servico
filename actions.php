<?php
// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}
/*
global $wpdb;
$outd_table_name = $wpdb->prefix . 'outdoor';

$outd_url = get_admin_url() . 'admin.php';
$oud_symbol = '?';

if (isset($_GET["page"])) {
    $outd_url = $outd_url . $oud_symbol . 'page=' . sanitize_text_field($_GET['page']);
    $oud_symbol = '&';
}
if (isset($_GET["orderby"])) {
    $outd_url = $outd_url . $oud_symbol . 'orderby=' . sanitize_text_field($_GET['orderby']);
    $oud_symbol = '&';
}
if (isset($_GET["order"])) {
    $outd_url = $outd_url . $oud_symbol . 'order=' . sanitize_text_field($_GET['order']);
    $oud_symbol = '&';
}
if (isset($_GET["paged"])) {
    $outd_url = $outd_url . $oud_symbol . 'paged=' . sanitize_text_field($_GET['paged']);
    $oud_symbol = '&';
}
if (isset($_GET["outdoor_status"])) {
    $outd_url = $outd_url . $oud_symbol . 'outdoor_status=' . sanitize_text_field($_GET['outdoor_status']);
    $oud_symbol = '&';
}

function outd_adjust_lines()
{
    global $wpdb, $outd_table_name;

    $result = $wpdb->get_results(" SELECT outdoor_id FROM  $outd_table_name ORDER BY outdoor_status,outdoor_order ");

    foreach ($result as $order => $page) {
        $wpdb->update(
            $outd_table_name,
            array(
                'outdoor_order' => $order + 1,
            ),
            array(
                'outdoor_id' => $page->outdoor_id,
            ),
            array('%d'),
            array('%d')
        );
    }

}

if (isset($_POST["action_row"]) && $_POST["action_row"] == 'edit') {

    $outd_field = sanitize_text_field($_POST["field"]);
    $outd_value = sanitize_text_field($_POST["value"]);
    $outd_id = sanitize_text_field($_POST["id_row"]);

    if ($outd_field == "outdoor_order") {
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT outdoor_id,outdoor_order FROM  $outd_table_name WHERE outdoor_id = %d", $outd_id)
        );
        if ($result[0]->outdoor_order < $outd_value) {
            $outd_value += 1;
        }

        $wpdb->query("UPDATE $outd_table_name SET outdoor_order = outdoor_order + 1 WHERE outdoor_order >= $outd_value");
    }

    $wpdb->query("UPDATE $outd_table_name SET $outd_field = '$outd_value' WHERE outdoor_id = $outd_id");

    outd_adjust_lines();
    header('Location: ' . $outd_url);
    exit;
}

if (isset($_POST["outd_add_media"])) {

    $ids = explode(',', sanitize_text_field($_POST["outd_add_media"]));

    $wpdb->query("UPDATE $outd_table_name SET outdoor_order = outdoor_order + " . count($ids) . " WHERE outdoor_status = 'active'");

    foreach ($ids as $order => $id) {
        if ($id > 0) {
            $wpdb->insert(
                $outd_table_name,
                array(
                    'post_id' => absint($id),
                    'outdoor_status' => 'active',
                    'outdoor_order' => absint($order),
                ),
                array('%d', '%s', '%d')
            );
        }
    }

    outd_adjust_lines();
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'delete') {
    $ids = [];
    if (is_array($_REQUEST["element"])) {
        foreach ($_REQUEST["element"] as $item) {
            $ids[] = absint($item);
        }
    } else {
        $ids[] = absint($_REQUEST["element"]);
    }

    foreach ($ids as $id) {
        $wpdb->delete(
            $outd_table_name,
            array(
                'outdoor_id' => $id,
            ),
            array('%d')
        );
    }

    outd_adjust_lines();

    header('Location: ' . $outd_url);
    exit;
}

if (isset($_REQUEST["action"]) && ($_REQUEST["action"] == 'active' || $_REQUEST["action"] == 'inactive')) {
    $ids = [];
    if (is_array($_REQUEST["element"])) {
        foreach ($_REQUEST["element"] as $item) {
            $ids[] = absint($item);
        }
    } else {
        $ids[] = absint($_REQUEST["element"]);
    }

    foreach ($ids as $id) {
        $wpdb->update(
            $outd_table_name,
            array(
                'outdoor_status' => sanitize_text_field($_REQUEST["action"]),
            ),
            array(
                'outdoor_id' => $id,
            ),
            array('%s'),
            array('%d')
        );
    }

    outd_adjust_lines();

    header('Location: ' . $outd_url);
    exit;
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'reinstall') {
    $r = outd_sql_create_outdoor_table();
    if ($r) {
        header('Location: ' . $outd_url);
        exit;
    }
}
*/