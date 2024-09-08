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
    $inline_script = <<<JS
    (function() {
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('escsrv_remove_item_button')) {
                var itemToRemove = event.target.closest('.escsrv_item');
                if (itemToRemove) {
                    itemToRemove.remove();
                }
            }
        });
        
        document.getElementById('escsrv_add_item_button').addEventListener('click', function() {
            var newItemHtml =  `$new_item_html`.replaceAll('[__index__]', generateUUID());
            var wrapper = document.getElementById('escsrv_itens_wrapper');
            wrapper.insertAdjacentHTML('beforeend', newItemHtml);
        });
    })();
    JS;

    $inline_style = "";
    if (get_post_type() === 'escsrv_escala') {
        $inline_style = <<<CSS
            #postdivrich, #postimagediv, #commentstatusdiv, #trackbacksdiv {
                display: none;
            }
        CSS;
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
    $inline_script = <<<JS
    (function() {
        document.getElementById('generate_pdf_button').addEventListener('click', function() {
            window.open('/wp-json/escsrv/v1/generate_pdf/$post_id', '_blank');
        });
    })();
    JS;

    wp_add_inline_script('escsrv-front-scripts', $inline_script);
}
add_action('wp_enqueue_scripts', 'escsrv_load_front_scripts');
