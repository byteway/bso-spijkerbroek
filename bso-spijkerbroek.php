<?php
/**
 * Plugin Name: BSO Spijkerbroek
 */

if (!defined('ABSPATH')) exit;

define('BSO_PATH', plugin_dir_path(__FILE__));
define('BSO_URL', plugin_dir_url(__FILE__));
define('BSO_VERSION', '0.1.0');

require_once BSO_PATH . 'includes/class-bso-spijkerbroek-activator.php';
require_once BSO_PATH . 'includes/class-bso-spijkerbroek-deactivator.php';

require_once BSO_PATH.'includes/class-bso-plugin.php';

register_activation_hook(__FILE__, array('BSO_Spijkerbroek_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('BSO_Spijkerbroek_Deactivator', 'deactivate'));

function bso_run(){
    $p = new BSO_Plugin();
    $p->run();
}

bso_run();
