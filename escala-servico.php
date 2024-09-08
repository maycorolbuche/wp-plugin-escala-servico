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

define('ESCSRV_URL', plugins_url('', __FILE__));
define('ESCSRV_URL_CSS', ESCSRV_URL . '/assets/css/');
define('ESCSRV_URL_JS', ESCSRV_URL . '/assets/js/');


require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

require_once plugin_dir_path(__FILE__) . 'functions/functions.php';
require_once plugin_dir_path(__FILE__) . 'functions/register.php';
require_once plugin_dir_path(__FILE__) . 'functions/scripts.php';
require_once plugin_dir_path(__FILE__) . 'functions/pdf.php';
require_once plugin_dir_path(__FILE__) . 'functions/post.php';
