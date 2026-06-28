<?php
if (!defined('ABSPATH')) exit;

class BSO_Spijkerbroek_Deactivator {
	public static function deactivate() {
		// Keep data intact on deactivate, only stop runtime automation.
		wp_clear_scheduled_hook('bso_spijkerbroek_recalculate_scores');

		update_option('bso_spijkerbroek_deactivated_at', current_time('mysql'));

		flush_rewrite_rules();
	}
}
