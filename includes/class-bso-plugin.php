<?php
class BSO_Plugin{
    public function run(){
        add_action('admin_menu', [$this,'menu']);
        add_action('wp_ajax_bso_dashboard_data', [$this,'ajax']);
    }

    public function menu(){
        add_menu_page('BSO','BSO','manage_options','bso-dashboard',[$this,'render']);
    }

    public function render(){
        echo '<div class="wrap"><h1>BSO Dashboard</h1><div id="app">Loading...</div></div>';
    }

    public function ajax(){
        wp_send_json_success(['html'=>'<p>Live dashboard werkt ✅</p>']);
    }
}
