<?php
if (!defined('ABSPATH')) exit;

class BSO_Spijkerbroek_Activator {
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$games_table = $wpdb->prefix . 'bso_games';
		$rounds_table = $wpdb->prefix . 'bso_game_rounds';
		$orgs_table = $wpdb->prefix . 'bso_organizations';
		$players_table = $wpdb->prefix . 'bso_players';
		$commitments_table = $wpdb->prefix . 'bso_commitments';
		$scores_table = $wpdb->prefix . 'bso_round_scores';
		$params_table = $wpdb->prefix . 'bso_game_parameters';
		$hr_table = $wpdb->prefix . 'bso_hr_requests';

		$sql_games = "CREATE TABLE {$games_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			description TEXT NULL,
			start_datetime DATETIME NULL,
			end_datetime DATETIME NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) {$charset_collate};";

		$sql_rounds = "CREATE TABLE {$rounds_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			game_id BIGINT(20) UNSIGNED NOT NULL,
			turn_number INT NOT NULL,
			start_datetime DATETIME NULL,
			end_datetime DATETIME NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_game_turn (game_id, turn_number),
			KEY game_id (game_id),
			KEY status (status)
		) {$charset_collate};";

		$sql_orgs = "CREATE TABLE {$orgs_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			game_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(190) NOT NULL,
			description TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_game_org_name (game_id, name),
			KEY game_id (game_id),
			KEY status (status)
		) {$charset_collate};";

		$sql_players = "CREATE TABLE {$players_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_user_id BIGINT(20) UNSIGNED NOT NULL,
			organization_id BIGINT(20) UNSIGNED NOT NULL,
			email VARCHAR(190) NULL,
			display_name VARCHAR(190) NULL,
			role_in_team VARCHAR(80) NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_user_org (wp_user_id, organization_id),
			KEY organization_id (organization_id)
		) {$charset_collate};";

		$sql_commitments = "CREATE TABLE {$commitments_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			game_id BIGINT(20) UNSIGNED NOT NULL,
			round_id BIGINT(20) UNSIGNED NOT NULL,
			organization_id BIGINT(20) UNSIGNED NOT NULL,
			theme VARCHAR(10) NULL,
			price_jeans DECIMAL(10,2) NOT NULL DEFAULT 0,
			price_factor DECIMAL(10,4) NOT NULL DEFAULT 0,
			advertisement_tv INT NOT NULL DEFAULT 0,
			advertisement_newspaper INT NOT NULL DEFAULT 0,
			advertisement_family_weekly INT NOT NULL DEFAULT 0,
			advertisement_luxury_weekly INT NOT NULL DEFAULT 0,
			marketing_research INT NOT NULL DEFAULT 0,
			production_segment_1 INT NOT NULL DEFAULT 0,
			production_segment_2 INT NOT NULL DEFAULT 0,
			production_segment_3 INT NOT NULL DEFAULT 0,
			distribution_form VARCHAR(50) NULL,
			hiring_staff INT NOT NULL DEFAULT 0,
			layoff_staff INT NOT NULL DEFAULT 0,
			total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
			turnover DECIMAL(12,2) NOT NULL DEFAULT 0,
			profit DECIMAL(12,2) NOT NULL DEFAULT 0,
			sale INT NOT NULL DEFAULT 0,
			market_index DECIMAL(8,4) NOT NULL DEFAULT 0,
			total_employees INT NOT NULL DEFAULT 0,
			media_total DECIMAL(12,2) NOT NULL DEFAULT 0,
			advertisement_factor DECIMAL(12,4) NOT NULL DEFAULT 0,
			formula_version VARCHAR(40) NOT NULL DEFAULT 'v1',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_round_org (game_id, round_id, organization_id),
			KEY round_id (round_id),
			KEY organization_id (organization_id)
		) {$charset_collate};";

		$sql_scores = "CREATE TABLE {$scores_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			game_id BIGINT(20) UNSIGNED NOT NULL,
			round_id BIGINT(20) UNSIGNED NOT NULL,
			organization_id BIGINT(20) UNSIGNED NOT NULL,
			turnover DECIMAL(12,2) NOT NULL DEFAULT 0,
			profit DECIMAL(12,2) NOT NULL DEFAULT 0,
			market_index DECIMAL(8,4) NOT NULL DEFAULT 0,
			rank_position INT NULL,
			cumulative_score DECIMAL(12,4) NOT NULL DEFAULT 0,
			formula_version VARCHAR(40) NOT NULL DEFAULT 'v1',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_round_score (game_id, round_id, organization_id),
			KEY round_id (round_id),
			KEY organization_id (organization_id)
		) {$charset_collate};";

		$sql_params = "CREATE TABLE {$params_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			game_id BIGINT(20) UNSIGNED NULL,
			variable_name VARCHAR(120) NOT NULL,
			numeric_value DECIMAL(14,4) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_game_variable (game_id, variable_name),
			KEY variable_name (variable_name)
		) {$charset_collate};";

		$sql_hr = "CREATE TABLE {$hr_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			game_id BIGINT(20) UNSIGNED NOT NULL,
			round_id BIGINT(20) UNSIGNED NOT NULL,
			organization_id BIGINT(20) UNSIGNED NOT NULL,
			request_type VARCHAR(30) NOT NULL DEFAULT 'resignation',
			requested_count INT NOT NULL DEFAULT 0,
			effective_round INT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			reason TEXT NULL,
			decision_note TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY round_org (round_id, organization_id),
			KEY status (status)
		) {$charset_collate};";

		dbDelta($sql_games);
		dbDelta($sql_rounds);
		dbDelta($sql_orgs);
		dbDelta($sql_players);
		dbDelta($sql_commitments);
		dbDelta($sql_scores);
		dbDelta($sql_params);
		dbDelta($sql_hr);

		$now = current_time('mysql');

		// Persist plugin lifecycle metadata.
		if (get_option('bso_spijkerbroek_installed_at') === false) {
			add_option('bso_spijkerbroek_installed_at', $now);
		}

		if (defined('BSO_VERSION')) {
			update_option('bso_spijkerbroek_version', BSO_VERSION);
		}

		$defaults = array(
			'max_players_per_team' => 8,
			'default_price_factor' => 1.00,
			'marketing_effect_factor' => 1.00,
			'production_cost' => 15.00,
			'number_of_turns' => 8,
			'target_price_theme_a' => 75.00,
			'target_price_theme_b' => 95.00,
			'target_price_theme_c' => 115.00,
		);

		foreach ($defaults as $name => $value) {
			$exists = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM {$params_table} WHERE game_id IS NULL AND variable_name = %s LIMIT 1",
				$name
			));

			if (empty($exists)) {
				$wpdb->insert(
					$params_table,
					array(
						'game_id' => null,
						'variable_name' => $name,
						'numeric_value' => $value,
					)
				);
			}
		}

		// Placeholder cron hook for future score recalculation jobs.
		if (!wp_next_scheduled('bso_spijkerbroek_recalculate_scores')) {
			wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'bso_spijkerbroek_recalculate_scores');
		}

		flush_rewrite_rules();
	}
}
