<?php
/**
 * Plugin Name: BSO Spijkerbroek
 */

if (!defined('ABSPATH')) exit;

define('BSO_PATH', plugin_dir_path(__FILE__));

require_once BSO_PATH.'includes/class-bso-plugin.php';

function bso_run(){
    $p = new BSO_Plugin();
    $p->run();
}

bso_run();
