<?php
// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

function escsrv_load_admin_scripts()
{
    wp_enqueue_media();
    wp_enqueue_style('escsrv-style',  ESCSRV_URL_CSS .  'backend.css', array(), filemtime(ESCSRV_URL_CSS .  'backend.css'), 'all');
    wp_enqueue_script('escsrv-scripts',  ESCSRV_URL_JS .  'backend.js', array(), filemtime(ESCSRV_URL_JS .  'backend.js'), true);

    $new_item_html = escsrv_render_item_fields([], '[__index__]');
    $inline_script = "
        var newItemHtml =  `" . $new_item_html . "`;
    ";

    $inline_style = "";
    if (get_post_type() === 'escsrv_escala') {
        $inline_style = "
            #postdivrich, #postimagediv, #commentstatusdiv, #trackbacksdiv {
                display: none;
            }
        ";
    }

    wp_add_inline_script('escsrv-scripts', $inline_script);
    wp_add_inline_style('escsrv-style', $inline_style);
}
add_action('admin_enqueue_scripts', 'escsrv_load_admin_scripts');

function escsrv_load_front_scripts()
{
    global $post;

    wp_enqueue_media();
    wp_enqueue_style('escsrv-style',  ESCSRV_URL_CSS .  'frontend.css', array(), filemtime(ESCSRV_URL_CSS .  'frontend.css'), 'all');
    wp_enqueue_script('escsrv-front-scripts',  ESCSRV_URL_JS .  'frontend.js', array(), filemtime(ESCSRV_URL_JS .  'frontend.js'), true);

    $post_id = $post->ID;
    $inline_script = "
        (function() {
            document.getElementById('generate_pdf_button').addEventListener('click', function() {
                window.open('/wp-json/escsrv/v1/generate_pdf/$post_id', '_blank');
            });
        })();
    ";

    wp_add_inline_script('escsrv-front-scripts', $inline_script);
}
add_action('wp_enqueue_scripts', 'escsrv_load_front_scripts');
