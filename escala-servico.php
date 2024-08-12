<?php

/**
 * @package Escala de Serviço
 * @version 1.0.0
 */
/*
Plugin Name: Escala de Serviço
Plugin URI: https://wordpress.org/plugins/escala-servico/
Description: Este plugin serve para criar escalas de serviço para sua igreja ou evento.
Version: 1.0.0
Requires at least: 5.0
Requires PHP: 7.0
Author: Mayco Rolbuche
Author URI: https://maycorolbuche.com.br/
License: GPLv2 or later
 */

// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}


// Registra o custom post type para "Escala de Serviço"
function escsrv_register_custom_post_type()
{
    $labels = array(
        'name'               => 'Escalas de Serviço',
        'singular_name'      => 'Escala de Serviço',
        'menu_name'          => 'Escala de Serviço',
        'name_admin_bar'     => 'Escala de Serviço',
        'add_new'            => 'Adicionar Nova Escala',
        'add_new_item'       => 'Adicionar Nova Escala de Serviço',
        'new_item'           => 'Nova Escala de Serviço',
        'edit_item'          => 'Editar Escala de Serviço',
        'view_item'          => 'Ver Escala de Serviço',
        'all_items'          => 'Todas as Escalas',
        'search_items'       => 'Buscar Escalas',
        'not_found'          => 'Nenhuma Escala de Serviço encontrada.',
        'not_found_in_trash' => 'Nenhuma Escala de Serviço encontrada no lixo.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'escala'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'editor', 'custom-fields'),
        'menu_icon'          => 'dashicons-calendar-alt',
    );

    register_post_type('escsrv_escala', $args);
}
add_action('init', 'escsrv_register_custom_post_type');


// Função de desinstalação
function escsrv_uninstall()
{
    global $wpdb;

    // Deletar todos os posts do custom post type 'escsrv_escala'
    $post_type = 'escsrv_escala';
    $posts = get_posts(array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }

    // Deletar metadados associados
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_escsrv_%'");

    // Deletar as entradas do post type no banco de dados (garantia extra)
    $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = '$post_type'");
}
// Registrar a função de desinstalação
register_uninstall_hook(__FILE__, 'escsrv_uninstall');


// Função de ativação
function escsrv_activate()
{
    escsrv_register_custom_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'escsrv_activate');

// Função de desativação
function escsrv_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'escsrv_deactivate');


/*
function escsrv_flush_rewrite_rules() {
    escsrv_register_custom_post_type();
    flush_rewrite_rules();
}
add_action('init', 'escsrv_flush_rewrite_rules');
*/

require_once plugin_dir_path(__FILE__) . 'functions.php';
