<?php
if (!defined('ABSPATH')) exit;

class BSO_Plugin {
    public function run() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'register_public_assets'));

        add_action('init', array($this, 'register_shortcodes'));

        add_action('admin_post_bso_submit_commitment', array($this, 'handle_commitment_submit'));
        add_action('admin_post_bso_create_game', array($this, 'handle_create_game'));
        add_action('admin_post_bso_create_rounds', array($this, 'handle_create_rounds'));
        add_action('admin_post_bso_create_organization', array($this, 'handle_create_organization'));
        add_action('admin_post_bso_assign_player', array($this, 'handle_assign_player'));
        add_action('admin_post_bso_remove_player_assignment', array($this, 'handle_remove_player_assignment'));
        add_action('admin_post_bso_create_demo_setup', array($this, 'handle_create_demo_setup'));
        add_action('admin_post_bso_update_round_status', array($this, 'handle_round_status_update'));
        add_action('admin_post_bso_update_hr_request', array($this, 'handle_hr_request_update'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('bso_spijkerbroek_recalculate_scores', array($this, 'handle_scheduled_recalculation'));

        add_action('wp_ajax_bso_dashboard_data', array($this, 'ajax_dashboard_data'));
        add_action('wp_ajax_nopriv_bso_dashboard_data', array($this, 'ajax_dashboard_data'));
    }

    public function register_admin_menu() {
        add_menu_page(
            'BSO Spijkerbroek',
            'Spijkerbroekenspel',
            'manage_options',
            'bso-spijkerbroek',
            array($this, 'render_admin_dashboard'),
            'dashicons-chart-line',
            30
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== 'toplevel_page_bso-spijkerbroek') {
            return;
        }

        wp_enqueue_style('bso-admin-css', BSO_URL . 'assets/css/admin.css', array(), BSO_VERSION);
        wp_enqueue_script('bso-admin-js', BSO_URL . 'assets/js/admin.js', array(), BSO_VERSION, true);
        wp_add_inline_script('bso-admin-js', 'var ajaxurl = ' . wp_json_encode(admin_url('admin-ajax.php')) . ';', 'before');
    }

    public function register_public_assets() {
        wp_register_style('bso-public-css', BSO_URL . 'assets/css/public.css', array(), BSO_VERSION);
        wp_register_script('bso-public-js', BSO_URL . 'assets/js/public.js', array(), BSO_VERSION, true);
        wp_add_inline_script('bso-public-js', 'var ajaxurl = ' . wp_json_encode(admin_url('admin-ajax.php')) . ';', 'before');
    }

    public function register_shortcodes() {
        add_shortcode('bso_score', array($this, 'render_score_shortcode'));
        add_shortcode('bso_commitment', array($this, 'render_commitment_shortcode'));
        add_shortcode('bso_team_dashboard', array($this, 'render_team_dashboard_shortcode'));
    }

    public function render_admin_dashboard() {
        $game_id = isset($_GET['game_id']) ? absint($_GET['game_id']) : 0;

        $admin_error = isset($_GET['bso_admin_error']) ? sanitize_text_field($_GET['bso_admin_error']) : '';
        $admin_success = isset($_GET['bso_admin_success']) ? sanitize_text_field($_GET['bso_admin_success']) : '';

        echo '<div class="wrap">';
        echo '<h1>BSO Spijkerbroek Dashboard</h1>';

        if ($admin_success !== '') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($admin_success) . '</p></div>';
        }
        if ($admin_error !== '') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($admin_error) . '</p></div>';
        }

        echo $this->render_game_setup_panel($game_id);
        echo $this->render_player_setup_panel($game_id);
        echo $this->render_round_management_panel($game_id);
        echo $this->render_hr_request_management_panel($game_id);
        echo '<div id="app" data-game-id="' . esc_attr((string) $game_id) . '"><p>Dashboard wordt geladen...</p></div>';
        echo '</div>';
    }

    public function render_score_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'game_id' => '0',
        ), $atts);

        wp_enqueue_style('bso-public-css');
        wp_enqueue_script('bso-public-js');

        return '<div id="app" data-game-id="' . esc_attr($atts['game_id']) . '"><p>Scoreoverzicht wordt geladen...</p></div>';
    }

    public function render_commitment_shortcode($atts = array()) {
        if (!is_user_logged_in()) {
            return '<div class="bso-commitment-login">Log in om een commitment in te dienen.</div>';
        }

        $atts = shortcode_atts(array(
            'game_id' => '0',
            'round_id' => '0',
            'organization_id' => '0',
            'theme' => 'A',
            'hide_ids' => '0',
        ), $atts);

        $game_id = absint($atts['game_id']);
        $round_id = absint($atts['round_id']);
        $organization_id = absint($atts['organization_id']);
        $resolved_context = null;

        if ($game_id <= 0 || $round_id <= 0 || $organization_id <= 0) {
            $resolved_context = $this->get_player_dashboard_context($game_id);
            if ($resolved_context) {
                $game_id = (int) $resolved_context['game_id'];
                $round_id = (int) $resolved_context['round_id'];
                $organization_id = (int) $resolved_context['organization_id'];
            }
        }

        $commitment = $this->get_commitment_record($game_id, $round_id, $organization_id);
        if (!empty($commitment['theme'])) {
            $atts['theme'] = (string) $commitment['theme'];
        }
        $round_label = $resolved_context ? ('Ronde ' . (int) $resolved_context['round_turn_number']) : ('Ronde #' . $round_id);
        $hide_ids = !empty($atts['hide_ids']) && $atts['hide_ids'] !== '0';
        $show_manual_ids = !$hide_ids && $resolved_context === null;
        $round_status = $resolved_context ? (string) $resolved_context['round_status'] : '';

        $error = isset($_GET['bso_error']) ? sanitize_text_field($_GET['bso_error']) : '';
        $success = isset($_GET['bso_success']) ? sanitize_text_field($_GET['bso_success']) : '';

        ob_start();
        ?>
        <div class="bso-commitment-wrap">
            <?php if ($resolved_context): ?>
                <div class="bso-commitment-context">
                    <p class="bso-commitment-context__eyebrow">Teamdashboard</p>
                    <h3><?php echo esc_html($resolved_context['game_name']); ?> - <?php echo esc_html($resolved_context['organization_name']); ?></h3>
                    <p>
                        <?php echo esc_html($round_label); ?>
                        <?php if ($round_status !== ''): ?>
                            <span class="bso-commitment-context__status">(<?php echo esc_html($round_status); ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            <?php if ($success === '1'): ?>
                <div class="notice notice-success"><p>Commitment opgeslagen.</p></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <?php if ($resolved_context && $round_status !== 'open'): ?>
                <div class="notice notice-warning"><p>Deze ronde is gesloten. Je kunt de commitment hier nog bekijken, maar niet meer aanpassen.</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bso_submit_commitment'); ?>
                <input type="hidden" name="action" value="bso_submit_commitment" />

                <?php if ($show_manual_ids): ?>
                    <p>
                        <label>Game ID<br />
                            <input type="number" min="1" name="game_id" required value="<?php echo esc_attr((string) $game_id); ?>" />
                        </label>
                    </p>
                    <p>
                        <label>Round ID<br />
                            <input type="number" min="1" name="round_id" required value="<?php echo esc_attr((string) $round_id); ?>" />
                        </label>
                    </p>
                    <p>
                        <label>Organization ID<br />
                            <input type="number" min="1" name="organization_id" required value="<?php echo esc_attr((string) $organization_id); ?>" />
                        </label>
                    </p>
                <?php else: ?>
                    <input type="hidden" name="game_id" value="<?php echo esc_attr((string) $game_id); ?>" />
                    <input type="hidden" name="round_id" value="<?php echo esc_attr((string) $round_id); ?>" />
                    <input type="hidden" name="organization_id" value="<?php echo esc_attr((string) $organization_id); ?>" />
                <?php endif; ?>
                <p>
                    <label>Theme<br />
                        <select name="theme">
                            <?php foreach (array('A', 'B', 'C') as $theme): ?>
                                <option value="<?php echo esc_attr($theme); ?>" <?php selected($atts['theme'], $theme); ?>><?php echo esc_html($theme); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </p>
                <p>
                    <label>Prijs jeans<br />
                        <input type="number" min="0" step="0.01" name="price_jeans" required value="<?php echo esc_attr(isset($commitment['price_jeans']) ? (string) $commitment['price_jeans'] : '0'); ?>" />
                    </label>
                </p>

                <h4>Reclame</h4>
                <p><label>Family Weekly <input type="number" min="0" step="1" name="advertisement_family_weekly" value="<?php echo esc_attr(isset($commitment['advertisement_family_weekly']) ? (string) $commitment['advertisement_family_weekly'] : '0'); ?>" /></label></p>
                <p><label>Luxury Weekly <input type="number" min="0" step="1" name="advertisement_luxury_weekly" value="<?php echo esc_attr(isset($commitment['advertisement_luxury_weekly']) ? (string) $commitment['advertisement_luxury_weekly'] : '0'); ?>" /></label></p>
                <p><label>Newspaper <input type="number" min="0" step="1" name="advertisement_newspaper" value="<?php echo esc_attr(isset($commitment['advertisement_newspaper']) ? (string) $commitment['advertisement_newspaper'] : '0'); ?>" /></label></p>
                <p><label>TV Spots <input type="number" min="0" step="1" name="advertisement_tv" value="<?php echo esc_attr(isset($commitment['advertisement_tv']) ? (string) $commitment['advertisement_tv'] : '0'); ?>" /></label></p>
                <p><label>Marketing Research <input type="number" min="0" step="1" name="marketing_research" value="<?php echo esc_attr(isset($commitment['marketing_research']) ? (string) $commitment['marketing_research'] : '0'); ?>" /></label></p>

                <h4>Productie</h4>
                <p><label>Segment 1 (tight) <input type="number" min="0" step="1" name="production_segment_1" value="<?php echo esc_attr(isset($commitment['production_segment_1']) ? (string) $commitment['production_segment_1'] : '0'); ?>" /></label></p>
                <p><label>Segment 2 (half-width) <input type="number" min="0" step="1" name="production_segment_2" value="<?php echo esc_attr(isset($commitment['production_segment_2']) ? (string) $commitment['production_segment_2'] : '0'); ?>" /></label></p>
                <p><label>Segment 3 (wide) <input type="number" min="0" step="1" name="production_segment_3" value="<?php echo esc_attr(isset($commitment['production_segment_3']) ? (string) $commitment['production_segment_3'] : '0'); ?>" /></label></p>

                <h4>Personeel</h4>
                <p><label>Aanname personeel <input type="number" min="0" step="1" name="hiring_staff" value="<?php echo esc_attr(isset($commitment['hiring_staff']) ? (string) $commitment['hiring_staff'] : '0'); ?>" /></label></p>
                <p><label>Ontslag personeel <input type="number" min="0" step="1" name="layoff_staff" value="<?php echo esc_attr(isset($commitment['layoff_staff']) ? (string) $commitment['layoff_staff'] : '0'); ?>" /></label></p>

                <p>
                    <label>Distribution form<br />
                        <input type="text" name="distribution_form" maxlength="50" value="<?php echo esc_attr(isset($commitment['distribution_form']) && $commitment['distribution_form'] !== '' ? (string) $commitment['distribution_form'] : 'standard'); ?>" />
                    </label>
                </p>

                <?php if ($round_status === '' || $round_status === 'open'): ?>
                    <p><button type="submit">Commitment opslaan</button></p>
                <?php else: ?>
                    <p class="bso-commitment-readonly">Deze ronde is gesloten.</p>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_team_dashboard_shortcode($atts = array()) {
        if (!is_user_logged_in()) {
            return '<div class="bso-team-dashboard bso-team-dashboard--login">Log in om je teamdashboard te bekijken.</div>';
        }

        $atts = shortcode_atts(array(
            'game_id' => '0',
        ), $atts);

        $context = $this->get_player_dashboard_context(absint($atts['game_id']));
        if (!$context) {
            return '<div class="bso-team-dashboard bso-team-dashboard--empty">Je bent nog niet gekoppeld aan een organisatie in een actieve game.</div>';
        }

        wp_enqueue_style('bso-public-css');
        wp_enqueue_script('bso-public-js');

        $scoreboard_html = $this->render_dashboard_table($context['standings_rows'], 'tussenstand', (int) $context['organization_id']);
        $commitment_status = !empty($context['commitment']) ? 'Opgeslagen' : 'Nog niet ingediend';
        $can_submit = (string) $context['round_status'] === 'open';

        ob_start();
        ?>
        <div class="bso-team-dashboard">
            <header class="bso-team-dashboard__hero">
                <div>
                    <p class="bso-team-dashboard__eyebrow">Teamdashboard</p>
                    <h2><?php echo esc_html($context['game_name']); ?></h2>
                    <p class="bso-team-dashboard__lede">
                        Je team: <strong><?php echo esc_html($context['organization_name']); ?></strong>
                        <?php if (!empty($context['role_in_team'])): ?>
                            <span class="bso-team-dashboard__role">· rol <?php echo esc_html($context['role_in_team']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="bso-team-dashboard__hero-badges">
                    <span class="bso-team-dashboard__badge">Ronde <?php echo esc_html((string) $context['round_turn_number']); ?></span>
                    <span class="bso-team-dashboard__badge bso-team-dashboard__badge--status"><?php echo esc_html($context['round_status']); ?></span>
                    <span class="bso-team-dashboard__badge bso-team-dashboard__badge--commitment"><?php echo esc_html($commitment_status); ?></span>
                </div>
            </header>

            <div class="bso-team-dashboard__grid">
                <section class="bso-team-dashboard__card">
                    <h3>Teamoverzicht</h3>
                    <dl class="bso-team-dashboard__facts">
                        <div>
                            <dt>Organisatie</dt>
                            <dd><?php echo esc_html($context['organization_name']); ?></dd>
                        </div>
                        <div>
                            <dt>Speler</dt>
                            <dd><?php echo esc_html($context['player_display_name']); ?></dd>
                        </div>
                        <div>
                            <dt>Rondestatus</dt>
                            <dd><?php echo esc_html($context['round_status']); ?></dd>
                        </div>
                        <div>
                            <dt>Commitment</dt>
                            <dd><?php echo esc_html($commitment_status); ?></dd>
                        </div>
                    </dl>

                    <?php if (!empty($context['organization_description'])): ?>
                        <p class="bso-team-dashboard__description"><?php echo esc_html($context['organization_description']); ?></p>
                    <?php endif; ?>
                </section>

                <section class="bso-team-dashboard__card bso-team-dashboard__card--scores">
                    <div class="bso-team-dashboard__card-head">
                        <h3>Tussenstand</h3>
                        <p>Jouw organisatie is gemarkeerd in de ranking.</p>
                    </div>
                    <div class="bso-team-dashboard__scores" data-bso-scoreboard data-game-id="<?php echo esc_attr((string) $context['game_id']); ?>">
                        <?php echo $scoreboard_html; ?>
                    </div>
                </section>

                <section class="bso-team-dashboard__card bso-team-dashboard__card--commitment">
                    <div class="bso-team-dashboard__card-head">
                        <h3>Commitment</h3>
                        <p><?php echo $can_submit ? 'Je kunt nu de commitment voor deze ronde indienen.' : 'Deze ronde is gesloten; je ziet hieronder de laatst opgeslagen versie.'; ?></p>
                    </div>
                    <?php if ($can_submit): ?>
                        <?php echo $this->render_commitment_shortcode(array(
                            'game_id' => (string) $context['game_id'],
                            'round_id' => (string) $context['round_id'],
                            'organization_id' => (string) $context['organization_id'],
                            'theme' => !empty($context['commitment']['theme']) ? (string) $context['commitment']['theme'] : 'A',
                            'hide_ids' => '1',
                        )); ?>
                    <?php else: ?>
                        <?php echo $this->render_commitment_summary_block($context); ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_commitment_submit() {
        if (!is_user_logged_in()) {
            wp_die('Je moet ingelogd zijn om een commitment in te dienen.');
        }

        check_admin_referer('bso_submit_commitment');

        try {
            $data = $this->validate_commitment_input($_POST);
            $this->assert_round_open($data['game_id'], $data['round_id']);
            $this->save_commitment($data);
            $this->recalculate_round_scores($data['game_id'], $data['round_id']);
            $this->redirect_with_status('1', '');
        } catch (Exception $e) {
            $this->redirect_with_status('', $e->getMessage());
        }
    }

    public function handle_create_game() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om een game aan te maken.');
        }

        check_admin_referer('bso_create_game');

        try {
            $name = isset($_POST['game_name']) ? sanitize_text_field($_POST['game_name']) : '';
            $description = isset($_POST['game_description']) ? sanitize_textarea_field($_POST['game_description']) : '';
            $status = isset($_POST['game_status']) ? sanitize_text_field($_POST['game_status']) : 'draft';
            $start_datetime = isset($_POST['start_datetime']) ? sanitize_text_field($_POST['start_datetime']) : '';
            $end_datetime = isset($_POST['end_datetime']) ? sanitize_text_field($_POST['end_datetime']) : '';

            if ($name === '') {
                throw new Exception('Game naam is verplicht.');
            }

            if (!in_array($status, array('draft', 'active', 'completed', 'closed'), true)) {
                $status = 'draft';
            }

            $game_id = $this->create_game_record($name, $description, $status, $start_datetime, $end_datetime);
            $this->redirect_admin_dashboard($game_id, 'Game is aangemaakt.', '');
        } catch (Exception $e) {
            $this->redirect_admin_dashboard(0, '', $e->getMessage());
        }
    }

    public function handle_create_rounds() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om rondes aan te maken.');
        }

        check_admin_referer('bso_create_rounds');

        try {
            $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $round_count = isset($_POST['round_count']) ? absint($_POST['round_count']) : 0;
            $default_status = isset($_POST['round_status']) ? sanitize_text_field($_POST['round_status']) : 'open';

            if ($game_id <= 0) {
                throw new Exception('Selecteer eerst een game.');
            }

            if ($round_count <= 0) {
                $round_count = (int) $this->get_game_parameter($game_id, 'number_of_turns', 8);
            }

            if ($round_count <= 0) {
                throw new Exception('Aantal rondes is ongeldig.');
            }

            if (!in_array($default_status, array('open', 'closed'), true)) {
                $default_status = 'open';
            }

            $created = $this->create_round_records($game_id, $round_count, $default_status);
            $this->redirect_admin_dashboard($game_id, $created . ' ronde(s) aangemaakt.', '');
        } catch (Exception $e) {
            $fallback_game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $this->redirect_admin_dashboard($fallback_game_id, '', $e->getMessage());
        }
    }

    public function handle_create_organization() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om organisaties aan te maken.');
        }

        check_admin_referer('bso_create_organization');

        try {
            $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $name = isset($_POST['organization_name']) ? sanitize_text_field($_POST['organization_name']) : '';
            $description = isset($_POST['organization_description']) ? sanitize_textarea_field($_POST['organization_description']) : '';

            if ($game_id <= 0) {
                throw new Exception('Selecteer eerst een game.');
            }

            if ($name === '') {
                throw new Exception('Organisatienaam is verplicht.');
            }

            $this->create_organization_record($game_id, $name, $description);
            $this->redirect_admin_dashboard($game_id, 'Organisatie is aangemaakt.', '');
        } catch (Exception $e) {
            $fallback_game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $this->redirect_admin_dashboard($fallback_game_id, '', $e->getMessage());
        }
    }

    public function handle_assign_player() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om spelers te koppelen.');
        }

        check_admin_referer('bso_assign_player');

        try {
            $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $organization_id = isset($_POST['organization_id']) ? absint($_POST['organization_id']) : 0;
            $wp_user_id = isset($_POST['wp_user_id']) ? absint($_POST['wp_user_id']) : 0;
            $role_in_team = isset($_POST['role_in_team']) ? sanitize_text_field($_POST['role_in_team']) : '';

            if ($game_id <= 0 || $organization_id <= 0 || $wp_user_id <= 0) {
                throw new Exception('Game, organisatie en gebruiker zijn verplicht.');
            }

            $this->assign_player_to_organization($game_id, $organization_id, $wp_user_id, $role_in_team);
            $this->redirect_admin_dashboard($game_id, 'Speler is gekoppeld aan de organisatie.', '');
        } catch (Exception $e) {
            $fallback_game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $this->redirect_admin_dashboard($fallback_game_id, '', $e->getMessage());
        }
    }

    public function handle_remove_player_assignment() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om spelers te ontkoppelen.');
        }

        check_admin_referer('bso_remove_player_assignment');

        try {
            $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $player_id = isset($_POST['player_id']) ? absint($_POST['player_id']) : 0;

            if ($game_id <= 0 || $player_id <= 0) {
                throw new Exception('Game en spelerkoppeling zijn verplicht.');
            }

            $this->remove_player_assignment($player_id);
            $this->redirect_admin_dashboard($game_id, 'Spelerkoppeling is verwijderd.', '');
        } catch (Exception $e) {
            $fallback_game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $this->redirect_admin_dashboard($fallback_game_id, '', $e->getMessage());
        }
    }

    public function handle_create_demo_setup() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om een demo setup aan te maken.');
        }

        check_admin_referer('bso_create_demo_setup');

        try {
            $suffix = wp_date('Y-m-d H:i');
            $game_name = 'Demo Spijkerbroekenspel ' . $suffix;
            $game_id = $this->create_game_record(
                $game_name,
                'Automatisch aangemaakte demo setup voor snelle test van de plugin.',
                'active',
                '',
                ''
            );

            $round_count = (int) $this->get_game_parameter($game_id, 'number_of_turns', 8);
            if ($round_count <= 0) {
                $round_count = 8;
            }

            $this->create_round_records($game_id, $round_count, 'open');

            $organization_ids = array();
            $organization_ids[] = $this->create_organization_record($game_id, 'Team Alpha', 'Demo team voor prijsfocus en stabiele productie.');
            $organization_ids[] = $this->create_organization_record($game_id, 'Team Beta', 'Demo team voor marketing- en groeiscenario\'s.');
            $organization_ids[] = $this->create_organization_record($game_id, 'Team Gamma', 'Demo team voor agressieve marktaandeelstrategie.');

            $assigned_count = 0;
            $demo_roles = array('teamlead', 'finance', 'marketing');
            $users = get_users(array(
                'orderby' => 'ID',
                'order' => 'ASC',
                'number' => 3,
            ));

            foreach ($users as $index => $user) {
                if (!isset($organization_ids[$index])) {
                    break;
                }

                $this->assign_player_to_organization(
                    $game_id,
                    (int) $organization_ids[$index],
                    (int) $user->ID,
                    $demo_roles[$index] ?? 'player'
                );
                $assigned_count++;
            }

            $message = 'Demo setup aangemaakt: 1 game, ' . $round_count . ' rondes, 3 organisaties';
            if ($assigned_count > 0) {
                $message .= ', ' . $assigned_count . ' spelerkoppeling(en)';
            }
            $message .= '.';

            $this->redirect_admin_dashboard($game_id, $message, '');
        } catch (Exception $e) {
            $this->redirect_admin_dashboard(0, '', $e->getMessage());
        }
    }

    public function handle_scheduled_recalculation() {
        global $wpdb;

        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $open_rounds = $wpdb->get_results("SELECT id, game_id, end_datetime FROM {$rounds_table} WHERE status = 'open'", ARRAY_A);

        if (empty($open_rounds)) {
            return;
        }

        $now_ts = strtotime(current_time('mysql'));

        foreach ($open_rounds as $round) {
            $game_id = (int) $round['game_id'];
            $round_id = (int) $round['id'];

            $this->recalculate_round_scores($game_id, $round_id);

            $end_ts = !empty($round['end_datetime']) ? strtotime((string) $round['end_datetime']) : false;
            if ($end_ts !== false && $end_ts <= $now_ts) {
                $this->close_round_and_apply_hr($game_id, $round_id);
            }
        }
    }

    public function handle_round_status_update() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om rondebeheer uit te voeren.');
        }

        check_admin_referer('bso_update_round_status');

        try {
            $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $round_id = isset($_POST['round_id']) ? absint($_POST['round_id']) : 0;
            $status_action = isset($_POST['status_action']) ? sanitize_text_field($_POST['status_action']) : '';

            if ($game_id <= 0 || $round_id <= 0) {
                throw new Exception('Game en ronde zijn verplicht.');
            }

            if (!in_array($status_action, array('open', 'close', 'lock'), true)) {
                throw new Exception('Ongeldige ronde-actie.');
            }

            if ($status_action === 'open') {
                $this->set_round_status($game_id, $round_id, 'open');
                $this->redirect_admin_dashboard($game_id, 'Ronde is geopend.', '');
            }

            $this->recalculate_round_scores($game_id, $round_id);
            $this->close_round_and_apply_hr($game_id, $round_id);

            $success_message = $status_action === 'lock'
                ? 'Ronde is gelockt en gesloten.'
                : 'Ronde is gesloten.';

            $this->redirect_admin_dashboard($game_id, $success_message, '');
        } catch (Exception $e) {
            $fallback_game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $this->redirect_admin_dashboard($fallback_game_id, '', $e->getMessage());
        }
    }

    public function handle_hr_request_update() {
        if (!current_user_can('manage_options')) {
            wp_die('Je hebt geen rechten om HR-aanvragen te beheren.');
        }

        check_admin_referer('bso_update_hr_request');

        try {
            $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
            $status_action = isset($_POST['status_action']) ? sanitize_text_field($_POST['status_action']) : '';
            $decision_note = isset($_POST['decision_note']) ? sanitize_textarea_field($_POST['decision_note']) : '';
            $effective_round = isset($_POST['effective_round']) ? absint($_POST['effective_round']) : 0;

            if ($game_id <= 0 || $request_id <= 0) {
                throw new Exception('Game en HR-aanvraag zijn verplicht.');
            }

            if (!in_array($status_action, array('approved', 'rejected', 'pending'), true)) {
                throw new Exception('Ongeldige HR-actie.');
            }

            $this->update_hr_request_status($game_id, $request_id, $status_action, $decision_note, $effective_round);

            $status_label = $status_action === 'approved' ? 'goedgekeurd' : ($status_action === 'rejected' ? 'afgewezen' : 'teruggezet naar pending');
            $this->redirect_admin_dashboard($game_id, 'HR-aanvraag is ' . $status_label . '.', '');
        } catch (Exception $e) {
            $fallback_game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
            $this->redirect_admin_dashboard($fallback_game_id, '', $e->getMessage());
        }
    }

    public function register_rest_routes() {
        register_rest_route('bso/v1', '/scores', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_scores'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('bso/v1', '/commitments', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_post_commitment'),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));

        register_rest_route('bso/v1', '/hr-requests', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_hr_requests'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'rest_post_hr_request'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ),
        ));

        register_rest_route('bso/v1', '/hr-requests/(?P<id>\d+)/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_update_hr_request_status'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
    }

    public function rest_get_scores($request) {
        try {
            $game_id = absint($request->get_param('game_id'));
            $round_id = absint($request->get_param('round_id'));

            if ($game_id <= 0) {
                $context = $this->resolve_dashboard_context(0);
                if (!$context) {
                    return new WP_REST_Response(array(
                        'status' => 'empty',
                        'summary' => array(),
                        'standings' => array(),
                        'final' => array(),
                    ), 200);
                }
                $game_id = (int) $context['game_id'];
            }

            if ($round_id <= 0) {
                $context = $this->resolve_dashboard_context($game_id);
                if (!$context) {
                    return new WP_REST_Response(array(
                        'status' => 'empty',
                        'summary' => array(),
                        'standings' => array(),
                        'final' => array(),
                    ), 200);
                }

                $round_id = (int) $context['latest_round_id'];
                $is_finalized = (bool) $context['is_finalized'];
                $turn_number = (int) $context['latest_turn_number'];
            } else {
                $turn_number = $this->get_round_turn_number($game_id, $round_id);
                $context = $this->resolve_dashboard_context($game_id);
                $is_finalized = $context ? (bool) $context['is_finalized'] : false;
            }

            $standings_rows = $this->get_scores_for_round($game_id, $round_id);
            $final_rows = $is_finalized ? $this->get_scores_for_round($game_id, $round_id) : array();

            return new WP_REST_Response(array(
                'status' => 'ok',
                'summary' => array(
                    'game_id' => $game_id,
                    'round_id' => $round_id,
                    'turn_number' => $turn_number,
                    'is_finalized' => $is_finalized,
                ),
                'standings' => $this->format_rows_for_response($standings_rows),
                'final' => $this->format_rows_for_response($final_rows),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('bso_rest_scores_error', $e->getMessage(), array('status' => 400));
        }
    }

    public function rest_post_commitment($request) {
        try {
            $payload = $request->get_json_params();
            if (!is_array($payload) || empty($payload)) {
                $payload = $request->get_params();
            }

            $data = $this->validate_commitment_input($payload);
            $this->assert_round_open($data['game_id'], $data['round_id']);
            $this->save_commitment($data);
            $this->recalculate_round_scores($data['game_id'], $data['round_id']);

            return new WP_REST_Response(array(
                'status' => 'ok',
                'message' => 'Commitment opgeslagen.',
                'data' => array(
                    'game_id' => $data['game_id'],
                    'round_id' => $data['round_id'],
                    'organization_id' => $data['organization_id'],
                ),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('bso_rest_commitment_error', $e->getMessage(), array('status' => 400));
        }
    }

    public function rest_get_hr_requests($request) {
        global $wpdb;

        $game_id = absint($request->get_param('game_id'));
        if ($game_id <= 0) {
            return new WP_Error('bso_rest_hr_game_required', 'game_id is verplicht.', array('status' => 400));
        }

        $hr_table = $wpdb->prefix . 'bso_hr_requests';
        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $orgs_table = $wpdb->prefix . 'bso_organizations';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    h.id,
                    h.game_id,
                    h.round_id,
                    r.turn_number,
                    h.organization_id,
                    COALESCE(o.name, CONCAT('Organisatie #', h.organization_id)) AS organization_name,
                    h.request_type,
                    h.requested_count,
                    h.effective_round,
                    h.status,
                    h.reason,
                    h.decision_note,
                    h.updated_at
                 FROM {$hr_table} h
                 LEFT JOIN {$rounds_table} r ON r.id = h.round_id
                 LEFT JOIN {$orgs_table} o ON o.id = h.organization_id
                 WHERE h.game_id = %d
                 ORDER BY h.round_id DESC, h.id DESC",
                $game_id
            ),
            ARRAY_A
        );

        return new WP_REST_Response(array(
            'status' => 'ok',
            'items' => $rows,
        ), 200);
    }

    public function rest_post_hr_request($request) {
        global $wpdb;

        try {
            $payload = $request->get_json_params();
            if (!is_array($payload) || empty($payload)) {
                $payload = $request->get_params();
            }

            $game_id = $this->positive_int($payload, 'game_id', 'Game ID');
            $round_id = $this->positive_int($payload, 'round_id', 'Round ID');
            $organization_id = $this->positive_int($payload, 'organization_id', 'Organization ID');
            $requested_count = $this->non_negative_int($payload, 'requested_count', 'Requested count');

            $request_type = isset($payload['request_type']) ? sanitize_text_field($payload['request_type']) : 'resignation';
            if ($request_type === '') {
                $request_type = 'resignation';
            }

            $reason = isset($payload['reason']) ? sanitize_textarea_field($payload['reason']) : '';

            $hr_table = $wpdb->prefix . 'bso_hr_requests';
            $inserted = $wpdb->insert(
                $hr_table,
                array(
                    'game_id' => $game_id,
                    'round_id' => $round_id,
                    'organization_id' => $organization_id,
                    'request_type' => $request_type,
                    'requested_count' => $requested_count,
                    'effective_round' => null,
                    'status' => 'pending',
                    'reason' => $reason,
                    'decision_note' => '',
                ),
                array('%d', '%d', '%d', '%s', '%d', null, '%s', '%s', '%s')
            );

            if ($inserted === false) {
                throw new Exception('HR-aanvraag opslaan is mislukt.');
            }

            return new WP_REST_Response(array(
                'status' => 'ok',
                'message' => 'HR-aanvraag opgeslagen.',
                'request_id' => (int) $wpdb->insert_id,
            ), 201);
        } catch (Exception $e) {
            return new WP_Error('bso_rest_hr_create_error', $e->getMessage(), array('status' => 400));
        }
    }

    public function rest_update_hr_request_status($request) {
        try {
            $payload = $request->get_json_params();
            if (!is_array($payload) || empty($payload)) {
                $payload = $request->get_params();
            }

            $request_id = absint($request->get_param('id'));
            $game_id = $this->positive_int($payload, 'game_id', 'Game ID');
            $status_action = isset($payload['status_action']) ? sanitize_text_field($payload['status_action']) : '';
            $decision_note = isset($payload['decision_note']) ? sanitize_textarea_field($payload['decision_note']) : '';
            $effective_round = isset($payload['effective_round']) ? absint($payload['effective_round']) : 0;

            if ($request_id <= 0) {
                throw new Exception('HR-aanvraag ID is verplicht.');
            }

            if (!in_array($status_action, array('approved', 'rejected', 'pending'), true)) {
                throw new Exception('Ongeldige HR-actie.');
            }

            $this->update_hr_request_status($game_id, $request_id, $status_action, $decision_note, $effective_round);

            return new WP_REST_Response(array(
                'status' => 'ok',
                'message' => 'HR-aanvraag bijgewerkt.',
                'request_id' => $request_id,
                'status_action' => $status_action,
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('bso_rest_hr_update_error', $e->getMessage(), array('status' => 400));
        }
    }

    private function validate_commitment_input(array $input) {
        $game_id = $this->positive_int($input, 'game_id', 'Game ID');
        $round_id = $this->positive_int($input, 'round_id', 'Round ID');
        $organization_id = $this->positive_int($input, 'organization_id', 'Organization ID');

        $theme = isset($input['theme']) ? strtoupper(sanitize_text_field($input['theme'])) : 'A';
        if (!in_array($theme, array('A', 'B', 'C'), true)) {
            throw new Exception('Theme moet A, B of C zijn.');
        }

        $distribution_form = isset($input['distribution_form']) ? sanitize_text_field($input['distribution_form']) : '';
        if (mb_strlen($distribution_form) > 50) {
            throw new Exception('Distribution form mag maximaal 50 tekens bevatten.');
        }

        return array(
            'game_id' => $game_id,
            'round_id' => $round_id,
            'organization_id' => $organization_id,
            'theme' => $theme,
            'price_jeans' => $this->non_negative_decimal($input, 'price_jeans', 'Prijs jeans'),
            'advertisement_tv' => $this->non_negative_int($input, 'advertisement_tv', 'Reclame TV'),
            'advertisement_newspaper' => $this->non_negative_int($input, 'advertisement_newspaper', 'Reclame newspaper'),
            'advertisement_family_weekly' => $this->non_negative_int($input, 'advertisement_family_weekly', 'Reclame family weekly'),
            'advertisement_luxury_weekly' => $this->non_negative_int($input, 'advertisement_luxury_weekly', 'Reclame luxury weekly'),
            'marketing_research' => $this->non_negative_int($input, 'marketing_research', 'Marketing research'),
            'production_segment_1' => $this->non_negative_int($input, 'production_segment_1', 'Productie segment 1'),
            'production_segment_2' => $this->non_negative_int($input, 'production_segment_2', 'Productie segment 2'),
            'production_segment_3' => $this->non_negative_int($input, 'production_segment_3', 'Productie segment 3'),
            'hiring_staff' => $this->non_negative_int($input, 'hiring_staff', 'Aanname personeel'),
            'layoff_staff' => $this->non_negative_int($input, 'layoff_staff', 'Ontslag personeel'),
            'distribution_form' => $distribution_form,
            'formula_version' => 'v1',
        );
    }

    private function assert_round_open($game_id, $round_id) {
        global $wpdb;
        $rounds_table = $wpdb->prefix . 'bso_game_rounds';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status FROM {$rounds_table} WHERE id = %d AND game_id = %d LIMIT 1",
                $round_id,
                $game_id
            ),
            ARRAY_A
        );

        if (!$row) {
            throw new Exception('Ronde niet gevonden voor deze game.');
        }

        if (($row['status'] ?? '') !== 'open') {
            throw new Exception('Ronde is gesloten; commitment kan niet meer worden gewijzigd.');
        }
    }

    private function save_commitment(array $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'bso_commitments';

        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE game_id = %d AND round_id = %d AND organization_id = %d LIMIT 1",
                $data['game_id'],
                $data['round_id'],
                $data['organization_id']
            )
        );

        $payload = array(
            'game_id' => $data['game_id'],
            'round_id' => $data['round_id'],
            'organization_id' => $data['organization_id'],
            'theme' => $data['theme'],
            'price_jeans' => $data['price_jeans'],
            'advertisement_tv' => $data['advertisement_tv'],
            'advertisement_newspaper' => $data['advertisement_newspaper'],
            'advertisement_family_weekly' => $data['advertisement_family_weekly'],
            'advertisement_luxury_weekly' => $data['advertisement_luxury_weekly'],
            'marketing_research' => $data['marketing_research'],
            'production_segment_1' => $data['production_segment_1'],
            'production_segment_2' => $data['production_segment_2'],
            'production_segment_3' => $data['production_segment_3'],
            'distribution_form' => $data['distribution_form'],
            'hiring_staff' => $data['hiring_staff'],
            'layoff_staff' => $data['layoff_staff'],
            'media_total' => (float) ($data['advertisement_tv'] + $data['advertisement_newspaper'] + $data['advertisement_family_weekly'] + $data['advertisement_luxury_weekly']),
            'total_amount' => 0,
            'turnover' => 0,
            'profit' => 0,
            'sale' => 0,
            'market_index' => 0,
            'total_employees' => 0,
            'advertisement_factor' => 0,
            'price_factor' => 0,
            'formula_version' => $data['formula_version'],
        );

        $format = array(
            '%d','%d','%d','%s','%f',
            '%d','%d','%d','%d','%d',
            '%d','%d','%d','%s','%d',
            '%d','%f','%f','%f','%f',
            '%d','%f','%d','%f','%f',
            '%s'
        );

        if ($existing_id > 0) {
            $updated = $wpdb->update($table, $payload, array('id' => $existing_id), $format, array('%d'));
            if ($updated === false) {
                throw new Exception('Commitment bijwerken is mislukt.');
            }
            return;
        }

        $inserted = $wpdb->insert($table, $payload, $format);
        if ($inserted === false) {
            throw new Exception('Commitment opslaan is mislukt.');
        }
    }

    private function recalculate_round_scores($game_id, $round_id) {
        global $wpdb;

        $commitments_table = $wpdb->prefix . 'bso_commitments';
        $scores_table = $wpdb->prefix . 'bso_round_scores';
        $current_turn_number = $this->get_round_turn_number($game_id, $round_id);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$commitments_table} WHERE game_id = %d AND round_id = %d ORDER BY organization_id ASC",
                $game_id,
                $round_id
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return;
        }

        $target_prices = array(
            'A' => $this->get_game_parameter($game_id, 'target_price_theme_a', 75.0),
            'B' => $this->get_game_parameter($game_id, 'target_price_theme_b', 95.0),
            'C' => $this->get_game_parameter($game_id, 'target_price_theme_c', 115.0),
        );

        $production_cost = $this->get_game_parameter($game_id, 'production_cost', 15.0);
        $base_round_demand = $this->get_game_parameter($game_id, 'base_round_demand', 100000.0);
        $hiring_cost = $this->get_game_parameter($game_id, 'hiring_cost', 50.0);
        $layoff_cost = $this->get_game_parameter($game_id, 'layoff_cost', 20.0);

        $attractiveness = array();
        $sum_attr = 0.0;

        foreach ($rows as $row) {
            $theme = isset($row['theme']) ? strtoupper((string) $row['theme']) : 'A';
            if (!isset($target_prices[$theme])) {
                $theme = 'A';
            }

            $price = (float) $row['price_jeans'];
            $target_price = max(1.0, (float) $target_prices[$theme]);
            $difference = abs($price - $target_price);
            $price_effect = 1.0 / (1.0 + ($difference / $target_price));

            $media_total = (float) $row['advertisement_tv']
                + (float) $row['advertisement_newspaper']
                + (float) $row['advertisement_family_weekly']
                + (float) $row['advertisement_luxury_weekly'];
            $ad_factor = 1.0 + ($media_total / 1000.0);

            $attr = max(0.0001, $price_effect * $ad_factor);
            $attractiveness[(int) $row['id']] = array(
                'price_effect' => $price_effect,
                'ad_factor' => $ad_factor,
                'media_total' => $media_total,
                'attr' => $attr,
            );
            $sum_attr += $attr;
        }

        if ($sum_attr <= 0) {
            $sum_attr = 1.0;
        }

        $score_rows = array();

        foreach ($rows as $row) {
            $commitment_id = (int) $row['id'];
            $organization_id = (int) $row['organization_id'];

            $production_total = (int) $row['production_segment_1']
                + (int) $row['production_segment_2']
                + (int) $row['production_segment_3'];

            $previous_total_employees = $this->get_previous_total_employees($game_id, $round_id, $organization_id);
            if ($previous_total_employees <= 0) {
                $previous_total_employees = 10;
            }

            $resignation_effect = 0;
            if ($current_turn_number > 0) {
                $resignation_effect = $this->get_effective_resignation_count(
                    $game_id,
                    $organization_id,
                    $current_turn_number
                );
            }

            $total_employees = max(
                0,
                $previous_total_employees
                + (int) $row['hiring_staff']
                - (int) $row['layoff_staff']
                - $resignation_effect
            );

            $max_production_capacity = $total_employees * 2500;
            $effective_production = min($production_total, $max_production_capacity);

            $market_index = $attractiveness[$commitment_id]['attr'] / $sum_attr;
            $potential_sale = (int) round($base_round_demand * $market_index);
            $sale = min($effective_production, $potential_sale);

            $price = (float) $row['price_jeans'];
            $turnover = round($sale * $price, 2);

            $media_total = $attractiveness[$commitment_id]['media_total'];
            $price_factor = round($attractiveness[$commitment_id]['price_effect'], 4);
            $advertisement_factor = round($attractiveness[$commitment_id]['ad_factor'], 4);

            $staff_cost = ((int) $row['hiring_staff'] * $hiring_cost) + ((int) $row['layoff_staff'] * $layoff_cost);
            $production_cost_total = $effective_production * $production_cost;
            $total_amount = round($production_cost_total + $media_total + $staff_cost + (float) $row['marketing_research'], 2);
            $profit = round($turnover - $total_amount, 2);

            $wpdb->update(
                $commitments_table,
                array(
                    'price_factor' => $price_factor,
                    'media_total' => $media_total,
                    'advertisement_factor' => $advertisement_factor,
                    'total_amount' => $total_amount,
                    'turnover' => $turnover,
                    'profit' => $profit,
                    'sale' => $sale,
                    'market_index' => $market_index,
                    'total_employees' => $total_employees,
                    'formula_version' => 'v1',
                ),
                array('id' => $commitment_id),
                array('%f', '%f', '%f', '%f', '%f', '%f', '%d', '%f', '%d', '%s'),
                array('%d')
            );

            $cumulative_score = $this->get_previous_cumulative_score($game_id, $round_id, $organization_id) + $profit;

            $score_payload = array(
                'game_id' => $game_id,
                'round_id' => $round_id,
                'organization_id' => $organization_id,
                'turnover' => $turnover,
                'profit' => $profit,
                'market_index' => $market_index,
                'rank_position' => null,
                'cumulative_score' => round($cumulative_score, 4),
                'formula_version' => 'v1',
            );

            $existing_score_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$scores_table} WHERE game_id = %d AND round_id = %d AND organization_id = %d LIMIT 1",
                    $game_id,
                    $round_id,
                    $organization_id
                )
            );

            if ($existing_score_id > 0) {
                $wpdb->update(
                    $scores_table,
                    $score_payload,
                    array('id' => $existing_score_id),
                    array('%d', '%d', '%d', '%f', '%f', '%f', '%d', '%f', '%s'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $scores_table,
                    $score_payload,
                    array('%d', '%d', '%d', '%f', '%f', '%f', '%d', '%f', '%s')
                );
            }

            $score_rows[] = array(
                'organization_id' => $organization_id,
                'profit' => $profit,
                'market_index' => $market_index,
                'cumulative_score' => $cumulative_score,
            );
        }

        usort($score_rows, function ($a, $b) {
            if ($a['cumulative_score'] === $b['cumulative_score']) {
                if ($a['profit'] === $b['profit']) {
                    return $b['market_index'] <=> $a['market_index'];
                }
                return $b['profit'] <=> $a['profit'];
            }
            return $b['cumulative_score'] <=> $a['cumulative_score'];
        });

        $rank = 1;
        foreach ($score_rows as $row) {
            $wpdb->update(
                $scores_table,
                array('rank_position' => $rank),
                array(
                    'game_id' => $game_id,
                    'round_id' => $round_id,
                    'organization_id' => $row['organization_id'],
                ),
                array('%d'),
                array('%d', '%d', '%d')
            );
            $rank++;
        }
    }

    private function close_round_and_apply_hr($game_id, $round_id) {
        global $wpdb;

        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $round = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, game_id, turn_number, status FROM {$rounds_table} WHERE id = %d AND game_id = %d LIMIT 1",
                $round_id,
                $game_id
            ),
            ARRAY_A
        );

        if (!$round || (string) $round['status'] !== 'open') {
            return;
        }

        $next_turn_number = ((int) $round['turn_number']) + 1;
        $this->approve_pending_resignation_requests($game_id, $round_id, $next_turn_number);

        $wpdb->update(
            $rounds_table,
            array('status' => 'closed'),
            array('id' => $round_id, 'game_id' => $game_id),
            array('%s'),
            array('%d', '%d')
        );
    }

    private function set_round_status($game_id, $round_id, $new_status) {
        global $wpdb;

        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $updated = $wpdb->update(
            $rounds_table,
            array('status' => $new_status),
            array('id' => $round_id, 'game_id' => $game_id),
            array('%s'),
            array('%d', '%d')
        );

        if ($updated === false) {
            throw new Exception('Ronde status wijzigen is mislukt.');
        }
    }

    private function update_hr_request_status($game_id, $request_id, $status_action, $decision_note, $effective_round) {
        global $wpdb;

        $hr_table = $wpdb->prefix . 'bso_hr_requests';
        $request = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, game_id, round_id, requested_count FROM {$hr_table} WHERE id = %d AND game_id = %d LIMIT 1",
                $request_id,
                $game_id
            ),
            ARRAY_A
        );

        if (!$request) {
            throw new Exception('HR-aanvraag niet gevonden voor deze game.');
        }

        $requested_count = max(0, (int) $request['requested_count']);
        if ($status_action === 'approved' && $requested_count <= 0) {
            throw new Exception('Aanvraag met requested_count 0 kan niet worden goedgekeurd.');
        }

        if ($status_action === 'approved') {
            if ($effective_round <= 0) {
                $effective_round = $this->get_round_turn_number($game_id, (int) $request['round_id']) + 1;
            }

            if ($effective_round <= 0) {
                throw new Exception('Effective round kon niet worden bepaald.');
            }

            $updated = $wpdb->update(
                $hr_table,
                array(
                    'status' => 'approved',
                    'effective_round' => $effective_round,
                    'decision_note' => $decision_note,
                ),
                array('id' => $request_id),
                array('%s', '%d', '%s'),
                array('%d')
            );

            if ($updated === false) {
                throw new Exception('HR-aanvraag kon niet worden bijgewerkt.');
            }
            return;
        }

        if ($status_action === 'rejected') {
            $updated = $wpdb->update(
                $hr_table,
                array(
                    'status' => 'rejected',
                    'effective_round' => null,
                    'decision_note' => $decision_note,
                ),
                array('id' => $request_id),
                array('%s', null, '%s'),
                array('%d')
            );

            if ($updated === false) {
                throw new Exception('HR-aanvraag kon niet worden bijgewerkt.');
            }
            return;
        }

        $updated = $wpdb->update(
            $hr_table,
            array(
                'status' => 'pending',
                'effective_round' => null,
                'decision_note' => $decision_note,
            ),
            array('id' => $request_id),
            array('%s', null, '%s'),
            array('%d')
        );

        if ($updated === false) {
            throw new Exception('HR-aanvraag kon niet worden bijgewerkt.');
        }
    }

    private function approve_pending_resignation_requests($game_id, $round_id, $effective_turn_number) {
        global $wpdb;

        $hr_table = $wpdb->prefix . 'bso_hr_requests';
        $requests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, requested_count
                 FROM {$hr_table}
                 WHERE game_id = %d AND round_id = %d AND request_type = %s AND status = %s",
                $game_id,
                $round_id,
                'resignation',
                'pending'
            ),
            ARRAY_A
        );

        if (empty($requests)) {
            return;
        }

        foreach ($requests as $request) {
            $requested_count = (int) $request['requested_count'];
            $new_status = $requested_count > 0 ? 'approved' : 'rejected';
            $note = $requested_count > 0
                ? sprintf('Automatisch goedgekeurd bij rondeafsluiting. Ingang vanaf beurt %d.', $effective_turn_number)
                : 'Automatisch afgewezen bij rondeafsluiting (requested_count is 0).';

            if ($requested_count > 0) {
                $wpdb->update(
                    $hr_table,
                    array(
                        'status' => $new_status,
                        'effective_round' => $effective_turn_number,
                        'decision_note' => $note,
                    ),
                    array('id' => (int) $request['id']),
                    array('%s', '%d', '%s'),
                    array('%d')
                );
                continue;
            }

            $wpdb->update(
                $hr_table,
                array(
                    'status' => $new_status,
                    'effective_round' => null,
                    'decision_note' => $note,
                ),
                array('id' => (int) $request['id']),
                array('%s', null, '%s'),
                array('%d')
            );
        }
    }

    private function get_round_turn_number($game_id, $round_id) {
        global $wpdb;

        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $turn_number = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT turn_number FROM {$rounds_table} WHERE id = %d AND game_id = %d LIMIT 1",
                $round_id,
                $game_id
            )
        );

        return (int) $turn_number;
    }

    private function get_effective_resignation_count($game_id, $organization_id, $turn_number) {
        global $wpdb;

        $hr_table = $wpdb->prefix . 'bso_hr_requests';
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(requested_count), 0)
                 FROM {$hr_table}
                 WHERE game_id = %d
                   AND organization_id = %d
                   AND request_type = %s
                   AND status = %s
                   AND effective_round = %d",
                $game_id,
                $organization_id,
                'resignation',
                'approved',
                $turn_number
            )
        );

        return max(0, (int) $count);
    }

    private function get_previous_total_employees($game_id, $round_id, $organization_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'bso_commitments';

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT total_employees
                 FROM {$table}
                 WHERE game_id = %d AND organization_id = %d AND round_id < %d
                 ORDER BY round_id DESC
                 LIMIT 1",
                $game_id,
                $organization_id,
                $round_id
            )
        );

        return (int) $value;
    }

    private function get_previous_cumulative_score($game_id, $round_id, $organization_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'bso_round_scores';

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT cumulative_score
                 FROM {$table}
                 WHERE game_id = %d AND organization_id = %d AND round_id < %d
                 ORDER BY round_id DESC
                 LIMIT 1",
                $game_id,
                $organization_id,
                $round_id
            )
        );

        return (float) $value;
    }

    private function get_game_parameter($game_id, $variable_name, $default_value) {
        global $wpdb;

        $table = $wpdb->prefix . 'bso_game_parameters';

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT numeric_value FROM {$table}
                 WHERE variable_name = %s AND (game_id = %d OR game_id IS NULL)
                 ORDER BY game_id DESC
                 LIMIT 1",
                $variable_name,
                $game_id
            )
        );

        if ($value === null) {
            return (float) $default_value;
        }

        return (float) $value;
    }

    private function positive_int(array $input, $key, $label) {
        $value = isset($input[$key]) ? (int) $input[$key] : 0;
        if ($value <= 0) {
            throw new Exception($label . ' moet groter dan 0 zijn.');
        }
        return $value;
    }

    private function non_negative_int(array $input, $key, $label) {
        $value = isset($input[$key]) ? (int) $input[$key] : 0;
        if ($value < 0) {
            throw new Exception($label . ' mag niet negatief zijn.');
        }
        return $value;
    }

    private function non_negative_decimal(array $input, $key, $label) {
        if (!isset($input[$key]) || $input[$key] === '') {
            throw new Exception($label . ' is verplicht.');
        }

        $raw = str_replace(',', '.', (string) $input[$key]);
        $value = (float) $raw;

        if (!is_finite($value) || $value < 0) {
            throw new Exception($label . ' is ongeldig.');
        }

        return round($value, 2);
    }

    private function redirect_with_status($success, $error_message) {
        $args = array();
        if ($success !== '') {
            $args['bso_success'] = $success;
        }
        if ($error_message !== '') {
			$args['bso_error'] = $error_message;
        }

        $target = wp_get_referer();
        if (!$target) {
            $target = home_url('/');
        }

        wp_safe_redirect(add_query_arg($args, $target));
        exit;
    }

    private function redirect_admin_dashboard($game_id, $success_message, $error_message) {
        $args = array(
            'page' => 'bso-spijkerbroek',
        );

        if ((int) $game_id > 0) {
            $args['game_id'] = (int) $game_id;
        }

        if ($success_message !== '') {
            $args['bso_admin_success'] = $success_message;
        }

        if ($error_message !== '') {
            $args['bso_admin_error'] = $error_message;
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private function render_game_setup_panel($selected_game_id) {
        global $wpdb;

        $games_table = $wpdb->prefix . 'bso_games';
        $orgs_table = $wpdb->prefix . 'bso_organizations';

        $games = $wpdb->get_results(
            "SELECT id, name, status FROM {$games_table} ORDER BY id DESC",
            ARRAY_A
        );

        $active_game_id = (int) $selected_game_id;
        if ($active_game_id <= 0 && !empty($games)) {
            $active_game_id = (int) $games[0]['id'];
        }

        $organization_count = 0;
        if ($active_game_id > 0) {
            $organization_count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$orgs_table} WHERE game_id = %d",
                    $active_game_id
                )
            );
        }

        $html = '<div class="bso-game-setup" style="margin:16px 0 24px 0; padding:16px; border:1px solid #dcdcde; background:#fff;">';
        $html .= '<h2 style="margin-top:0;">Game Setup</h2>';
        $html .= '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">';

        $html .= '<div style="padding:12px; border:1px solid #e0e0e0; background:#f8fbff;">';
        $html .= '<h3 style="margin-top:0;">Quick Start / Demo Setup</h3>';
        $html .= '<p>Maakt direct een demo game aan met standaardrondes, 3 organisaties en koppelt waar mogelijk de eerste WordPress-gebruikers.</p>';
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $html .= '<input type="hidden" name="action" value="bso_create_demo_setup" />';
        $html .= wp_nonce_field('bso_create_demo_setup', '_wpnonce', true, false);
        $html .= '<p><button type="submit" class="button button-primary">Demo setup aanmaken</button></p>';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div style="padding:12px; border:1px solid #e0e0e0;">';
        $html .= '<h3 style="margin-top:0;">Nieuwe game</h3>';
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $html .= '<input type="hidden" name="action" value="bso_create_game" />';
        $html .= wp_nonce_field('bso_create_game', '_wpnonce', true, false);
        $html .= '<p><label>Naam<br /><input type="text" name="game_name" required style="width:100%;" /></label></p>';
        $html .= '<p><label>Beschrijving<br /><textarea name="game_description" rows="4" style="width:100%;"></textarea></label></p>';
        $html .= '<p><label>Status<br /><select name="game_status"><option value="draft">draft</option><option value="active">active</option></select></label></p>';
        $html .= '<p><label>Startdatum/tijd<br /><input type="datetime-local" name="start_datetime" style="width:100%;" /></label></p>';
        $html .= '<p><label>Einddatum/tijd<br /><input type="datetime-local" name="end_datetime" style="width:100%;" /></label></p>';
        $html .= '<p><button type="submit" class="button button-primary">Game aanmaken</button></p>';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div style="padding:12px; border:1px solid #e0e0e0;">';
        $html .= '<h3 style="margin-top:0;">Rondes genereren</h3>';
        if (empty($games)) {
            $html .= '<p>Maak eerst een game aan.</p>';
        } else {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            $html .= '<input type="hidden" name="action" value="bso_create_rounds" />';
            $html .= wp_nonce_field('bso_create_rounds', '_wpnonce', true, false);
            $html .= '<p><label>Game<br />' . $this->render_game_select('game_id', $games, $active_game_id) . '</label></p>';
            $html .= '<p><label>Aantal rondes<br /><input type="number" min="1" max="30" name="round_count" value="8" style="width:100%;" /></label></p>';
            $html .= '<p><label>Standaard status<br /><select name="round_status"><option value="open">open</option><option value="closed">closed</option></select></label></p>';
            $html .= '<p><button type="submit" class="button">Rondes aanmaken</button></p>';
            $html .= '</form>';
        }
        $html .= '</div>';

        $html .= '<div style="padding:12px; border:1px solid #e0e0e0;">';
        $html .= '<h3 style="margin-top:0;">Organisatie toevoegen</h3>';
        if (empty($games)) {
            $html .= '<p>Maak eerst een game aan.</p>';
        } else {
            $html .= '<p>Huidige organisaties in geselecteerde game: <strong>' . esc_html((string) $organization_count) . '</strong></p>';
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            $html .= '<input type="hidden" name="action" value="bso_create_organization" />';
            $html .= wp_nonce_field('bso_create_organization', '_wpnonce', true, false);
            $html .= '<p><label>Game<br />' . $this->render_game_select('game_id', $games, $active_game_id) . '</label></p>';
            $html .= '<p><label>Organisatienaam<br /><input type="text" name="organization_name" required style="width:100%;" /></label></p>';
            $html .= '<p><label>Beschrijving<br /><textarea name="organization_description" rows="4" style="width:100%;"></textarea></label></p>';
            $html .= '<p><button type="submit" class="button">Organisatie toevoegen</button></p>';
            $html .= '</form>';
        }
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function render_player_setup_panel($selected_game_id) {
        global $wpdb;

        $games_table = $wpdb->prefix . 'bso_games';
        $players_table = $wpdb->prefix . 'bso_players';

        $games = $wpdb->get_results(
            "SELECT id, name, status FROM {$games_table} ORDER BY id DESC",
            ARRAY_A
        );

        $active_game_id = (int) $selected_game_id;
        if ($active_game_id <= 0 && !empty($games)) {
            $active_game_id = (int) $games[0]['id'];
        }

        $organizations = $active_game_id > 0 ? $this->get_organizations_for_game($active_game_id) : array();
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email'),
        ));

        $assignments = array();
        if ($active_game_id > 0) {
            $org_ids = array_map(function ($org) {
                return (int) $org['id'];
            }, $organizations);

            if (!empty($org_ids)) {
                $placeholders = implode(',', array_fill(0, count($org_ids), '%d'));
                $query = $wpdb->prepare(
                    "SELECT p.id, p.wp_user_id, p.organization_id, p.email, p.display_name, p.role_in_team, o.name AS organization_name
                     FROM {$players_table} p
                     INNER JOIN {$wpdb->prefix}bso_organizations o ON o.id = p.organization_id
                     WHERE p.organization_id IN ({$placeholders})
                     ORDER BY o.name ASC, p.display_name ASC",
                    $org_ids
                );
                $assignments = $wpdb->get_results($query, ARRAY_A);
            }
        }

        $html = '<div class="bso-player-setup" style="margin:16px 0 24px 0; padding:16px; border:1px solid #dcdcde; background:#fff;">';
        $html .= '<h2 style="margin-top:0;">Player Setup</h2>';

        if (empty($games)) {
            $html .= '<p>Maak eerst een game aan voordat je spelers koppelt.</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<div style="display:grid; grid-template-columns: minmax(320px, 420px) 1fr; gap:16px;">';

        $html .= '<div style="padding:12px; border:1px solid #e0e0e0;">';
        $html .= '<h3 style="margin-top:0;">Gebruiker koppelen</h3>';

        if (empty($organizations)) {
            $html .= '<p>Voeg eerst organisaties toe aan de geselecteerde game.</p>';
        } elseif (empty($users)) {
            $html .= '<p>Geen WordPress-gebruikers gevonden om te koppelen.</p>';
        } else {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            $html .= '<input type="hidden" name="action" value="bso_assign_player" />';
            $html .= wp_nonce_field('bso_assign_player', '_wpnonce', true, false);
            $html .= '<p><label>Game<br />' . $this->render_game_select('game_id', $games, $active_game_id) . '</label></p>';
            $html .= '<p><label>Organisatie<br />' . $this->render_organization_select('organization_id', $organizations, 0) . '</label></p>';
            $html .= '<p><label>WordPress-gebruiker<br />' . $this->render_user_select('wp_user_id', $users, 0) . '</label></p>';
            $html .= '<p><label>Rol in team<br /><input type="text" name="role_in_team" maxlength="80" placeholder="bijv. teamlead, finance, marketing" style="width:100%;" /></label></p>';
            $html .= '<p><button type="submit" class="button button-primary">Koppel speler</button></p>';
            $html .= '</form>';
        }

        $html .= '</div>';

        $html .= '<div style="padding:12px; border:1px solid #e0e0e0;">';
        $html .= '<h3 style="margin-top:0;">Bestaande koppelingen</h3>';

        if (empty($assignments)) {
            $html .= '<p>Geen spelerkoppelingen gevonden voor deze game.</p>';
        } else {
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr><th>Gebruiker</th><th>E-mail</th><th>Organisatie</th><th>Rol</th><th>Actie</th></tr></thead><tbody>';

            foreach ($assignments as $assignment) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html((string) ($assignment['display_name'] ?: ('User #' . (int) $assignment['wp_user_id']))) . '</td>';
                $html .= '<td>' . esc_html((string) ($assignment['email'] ?: '-')) . '</td>';
                $html .= '<td>' . esc_html((string) $assignment['organization_name']) . '</td>';
                $html .= '<td>' . esc_html((string) ($assignment['role_in_team'] ?: '-')) . '</td>';
                $html .= '<td>' . $this->render_player_remove_form($active_game_id, (int) $assignment['id']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function render_game_select($field_name, array $games, $selected_game_id) {
        $html = '<select name="' . esc_attr($field_name) . '" style="width:100%;">';
        foreach ($games as $game) {
            $label = '#' . (int) $game['id'] . ' - ' . (string) $game['name'] . ' (' . (string) $game['status'] . ')';
            $html .= '<option value="' . esc_attr((string) $game['id']) . '" ' . selected((int) $selected_game_id, (int) $game['id'], false) . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function render_organization_select($field_name, array $organizations, $selected_organization_id) {
        $html = '<select name="' . esc_attr($field_name) . '" style="width:100%;">';
        foreach ($organizations as $organization) {
            $html .= '<option value="' . esc_attr((string) $organization['id']) . '" ' . selected((int) $selected_organization_id, (int) $organization['id'], false) . '>' . esc_html((string) $organization['name']) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function render_user_select($field_name, array $users, $selected_user_id) {
        $html = '<select name="' . esc_attr($field_name) . '" style="width:100%;">';
        foreach ($users as $user) {
            $label = $user->display_name . ' (' . $user->user_email . ')';
            $html .= '<option value="' . esc_attr((string) $user->ID) . '" ' . selected((int) $selected_user_id, (int) $user->ID, false) . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function render_player_remove_form($game_id, $player_id) {
        $html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;">';
        $html .= '<input type="hidden" name="action" value="bso_remove_player_assignment" />';
        $html .= '<input type="hidden" name="game_id" value="' . esc_attr((string) ((int) $game_id)) . '" />';
        $html .= '<input type="hidden" name="player_id" value="' . esc_attr((string) ((int) $player_id)) . '" />';
        $html .= wp_nonce_field('bso_remove_player_assignment', '_wpnonce', true, false);
        $html .= '<button type="submit" class="button button-secondary">Ontkoppel</button>';
        $html .= '</form>';

        return $html;
    }

    private function create_game_record($name, $description, $status, $start_datetime, $end_datetime) {
        global $wpdb;

        $games_table = $wpdb->prefix . 'bso_games';
        $inserted = $wpdb->insert(
            $games_table,
            array(
                'name' => $name,
                'description' => $description,
                'start_datetime' => $this->normalize_admin_datetime($start_datetime),
                'end_datetime' => $this->normalize_admin_datetime($end_datetime),
                'status' => $status,
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            throw new Exception('Game aanmaken is mislukt.');
        }

        return (int) $wpdb->insert_id;
    }

    private function create_round_records($game_id, $round_count, $default_status) {
        global $wpdb;

        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $existing_turns = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT turn_number FROM {$rounds_table} WHERE game_id = %d",
                $game_id
            )
        );
        $existing_turn_map = array_fill_keys(array_map('intval', $existing_turns), true);

        $created = 0;
        for ($turn_number = 1; $turn_number <= $round_count; $turn_number++) {
            if (isset($existing_turn_map[$turn_number])) {
                continue;
            }

            $inserted = $wpdb->insert(
                $rounds_table,
                array(
                    'game_id' => $game_id,
                    'turn_number' => $turn_number,
                    'status' => $default_status,
                ),
                array('%d', '%d', '%s')
            );

            if ($inserted === false) {
                throw new Exception('Rondes aanmaken is mislukt bij beurt ' . $turn_number . '.');
            }

            $created++;
        }

        return $created;
    }

    private function create_organization_record($game_id, $name, $description) {
        global $wpdb;

        $orgs_table = $wpdb->prefix . 'bso_organizations';
        $inserted = $wpdb->insert(
            $orgs_table,
            array(
                'game_id' => $game_id,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
            ),
            array('%d', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            throw new Exception('Organisatie aanmaken is mislukt. Mogelijk bestaat de naam al binnen deze game.');
        }

        return (int) $wpdb->insert_id;
    }

    private function assign_player_to_organization($game_id, $organization_id, $wp_user_id, $role_in_team) {
        global $wpdb;

        $organization = $this->get_organization_record($organization_id, $game_id);
        if (!$organization) {
            throw new Exception('Organisatie hoort niet bij de geselecteerde game.');
        }

        $user = get_user_by('id', $wp_user_id);
        if (!$user) {
            throw new Exception('WordPress-gebruiker niet gevonden.');
        }

        $players_table = $wpdb->prefix . 'bso_players';
        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$players_table} WHERE wp_user_id = %d AND organization_id = %d LIMIT 1",
                $wp_user_id,
                $organization_id
            )
        );

        $payload = array(
            'wp_user_id' => $wp_user_id,
            'organization_id' => $organization_id,
            'email' => (string) $user->user_email,
            'display_name' => (string) $user->display_name,
            'role_in_team' => $role_in_team,
        );

        if ($existing_id > 0) {
            $updated = $wpdb->update(
                $players_table,
                $payload,
                array('id' => $existing_id),
                array('%d', '%d', '%s', '%s', '%s'),
                array('%d')
            );

            if ($updated === false) {
                throw new Exception('Spelerkoppeling bijwerken is mislukt.');
            }

            return $existing_id;
        }

        $inserted = $wpdb->insert(
            $players_table,
            $payload,
            array('%d', '%d', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            throw new Exception('Spelerkoppeling aanmaken is mislukt.');
        }

        return (int) $wpdb->insert_id;
    }

    private function remove_player_assignment($player_id) {
        global $wpdb;

        $players_table = $wpdb->prefix . 'bso_players';
        $deleted = $wpdb->delete(
            $players_table,
            array('id' => $player_id),
            array('%d')
        );

        if ($deleted === false) {
            throw new Exception('Spelerkoppeling verwijderen is mislukt.');
        }
    }

    private function get_organizations_for_game($game_id) {
        global $wpdb;

        $orgs_table = $wpdb->prefix . 'bso_organizations';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, description, status FROM {$orgs_table} WHERE game_id = %d ORDER BY name ASC",
                $game_id
            ),
            ARRAY_A
        );
    }

    private function get_organization_record($organization_id, $game_id) {
        global $wpdb;

        $orgs_table = $wpdb->prefix . 'bso_organizations';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, game_id, name FROM {$orgs_table} WHERE id = %d AND game_id = %d LIMIT 1",
                $organization_id,
                $game_id
            ),
            ARRAY_A
        );
    }

    private function normalize_admin_datetime($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function render_round_management_panel($selected_game_id) {
        global $wpdb;

        $games_table = $wpdb->prefix . 'bso_games';
        $rounds_table = $wpdb->prefix . 'bso_game_rounds';

        $games = $wpdb->get_results(
            "SELECT id, name, status FROM {$games_table} ORDER BY id DESC",
            ARRAY_A
        );

        $html = '<div class="bso-round-management" style="margin:16px 0 24px 0; padding:16px; border:1px solid #dcdcde; background:#fff;">';
        $html .= '<h2 style="margin-top:0;">Rondebeheer</h2>';

        if (empty($games)) {
            $html .= '<p>Geen games gevonden. Voeg eerst een game en rondes toe.</p>';
            $html .= '</div>';
            return $html;
        }

        $active_game_id = (int) $selected_game_id;
        if ($active_game_id <= 0) {
            $active_game_id = (int) $games[0]['id'];
        }

        $html .= '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" style="margin-bottom:12px;">';
        $html .= '<input type="hidden" name="page" value="bso-spijkerbroek" />';
        $html .= '<label for="bso_game_id"><strong>Game:</strong></label> ';
        $html .= '<select id="bso_game_id" name="game_id">';
        foreach ($games as $game) {
            $label = '#' . (int) $game['id'] . ' - ' . (string) $game['name'] . ' (' . (string) $game['status'] . ')';
            $html .= '<option value="' . esc_attr((string) $game['id']) . '" ' . selected($active_game_id, (int) $game['id'], false) . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select> ';
        $html .= '<button type="submit" class="button">Toon rondes</button>';
        $html .= '</form>';

        $rounds = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, turn_number, status, start_datetime, end_datetime
                 FROM {$rounds_table}
                 WHERE game_id = %d
                 ORDER BY turn_number ASC",
                $active_game_id
            ),
            ARRAY_A
        );

        if (empty($rounds)) {
            $html .= '<p>Geen rondes gevonden voor deze game.</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>Ronde ID</th><th>Beurt</th><th>Status</th><th>Start</th><th>Einde</th><th>Acties</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rounds as $round) {
            $round_id = (int) $round['id'];
            $status = (string) $round['status'];

            $html .= '<tr>';
            $html .= '<td>' . esc_html((string) $round_id) . '</td>';
            $html .= '<td>' . esc_html((string) ((int) $round['turn_number'])) . '</td>';
            $html .= '<td><strong>' . esc_html($status) . '</strong></td>';
            $html .= '<td>' . esc_html((string) ($round['start_datetime'] ?: '-')) . '</td>';
            $html .= '<td>' . esc_html((string) ($round['end_datetime'] ?: '-')) . '</td>';
            $html .= '<td>';

            if ($status === 'open') {
                $html .= $this->render_round_status_form($active_game_id, $round_id, 'close', 'Sluit');
                $html .= ' ';
                $html .= $this->render_round_status_form($active_game_id, $round_id, 'lock', 'Lock');
            } else {
                $html .= $this->render_round_status_form($active_game_id, $round_id, 'open', 'Open');
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    private function render_hr_request_management_panel($selected_game_id) {
        global $wpdb;

        $games_table = $wpdb->prefix . 'bso_games';
        $hr_table = $wpdb->prefix . 'bso_hr_requests';
        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $orgs_table = $wpdb->prefix . 'bso_organizations';

        $games = $wpdb->get_results(
            "SELECT id, name, status FROM {$games_table} ORDER BY id DESC",
            ARRAY_A
        );

        $html = '<div class="bso-hr-management" style="margin:16px 0 24px 0; padding:16px; border:1px solid #dcdcde; background:#fff;">';
        $html .= '<h2 style="margin-top:0;">HR-aanvraagbeheer</h2>';

        if (empty($games)) {
            $html .= '<p>Geen games gevonden. Voeg eerst een game en HR-aanvragen toe.</p>';
            $html .= '</div>';
            return $html;
        }

        $active_game_id = (int) $selected_game_id;
        if ($active_game_id <= 0) {
            $active_game_id = (int) $games[0]['id'];
        }

        $requests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    h.id,
                    h.round_id,
                    r.turn_number,
                    h.organization_id,
                    COALESCE(o.name, CONCAT('Organisatie #', h.organization_id)) AS organization_name,
                    h.request_type,
                    h.requested_count,
                    h.effective_round,
                    h.status,
                    h.reason,
                    h.decision_note,
                    h.updated_at
                 FROM {$hr_table} h
                 LEFT JOIN {$rounds_table} r ON r.id = h.round_id
                 LEFT JOIN {$orgs_table} o ON o.id = h.organization_id
                 WHERE h.game_id = %d
                 ORDER BY
                    CASE h.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END,
                    h.round_id DESC,
                    h.id DESC",
                $active_game_id
            ),
            ARRAY_A
        );

        if (empty($requests)) {
            $html .= '<p>Geen HR-aanvragen gevonden voor deze game.</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th><th>Ronde</th><th>Organisatie</th><th>Type</th><th>Aantal</th><th>Status</th><th>Effective round</th><th>Reason</th><th>Decision note</th><th>Actie</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($requests as $request) {
            $request_id = (int) $request['id'];
            $current_status = (string) $request['status'];
            $turn_label = !empty($request['turn_number']) ? ((string) ((int) $request['turn_number'])) : '-';
            $effective_round = !empty($request['effective_round']) ? (int) $request['effective_round'] : '';

            $html .= '<tr>';
            $html .= '<td>' . esc_html((string) $request_id) . '</td>';
            $html .= '<td>#' . esc_html((string) ((int) $request['round_id'])) . ' / beurt ' . esc_html($turn_label) . '</td>';
            $html .= '<td>' . esc_html((string) $request['organization_name']) . '</td>';
            $html .= '<td>' . esc_html((string) $request['request_type']) . '</td>';
            $html .= '<td>' . esc_html((string) ((int) $request['requested_count'])) . '</td>';
            $html .= '<td><strong>' . esc_html($current_status) . '</strong></td>';
            $html .= '<td>' . esc_html($effective_round === '' ? '-' : (string) $effective_round) . '</td>';
            $html .= '<td>' . esc_html((string) ($request['reason'] ?: '-')) . '</td>';
            $html .= '<td>' . esc_html((string) ($request['decision_note'] ?: '-')) . '</td>';
            $html .= '<td>';

            $html .= $this->render_hr_request_action_form($active_game_id, $request_id, 'approved', 'Approve', (string) ($request['decision_note'] ?: ''), $effective_round);
            $html .= $this->render_hr_request_action_form($active_game_id, $request_id, 'rejected', 'Reject', (string) ($request['decision_note'] ?: ''), '');
            if ($current_status !== 'pending') {
                $html .= $this->render_hr_request_action_form($active_game_id, $request_id, 'pending', 'Reset', (string) ($request['decision_note'] ?: ''), '');
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    private function render_hr_request_action_form($game_id, $request_id, $status_action, $label, $decision_note, $effective_round) {
        $html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:block; margin:0 0 8px 0;">';
        $html .= '<input type="hidden" name="action" value="bso_update_hr_request" />';
        $html .= '<input type="hidden" name="game_id" value="' . esc_attr((string) ((int) $game_id)) . '" />';
        $html .= '<input type="hidden" name="request_id" value="' . esc_attr((string) ((int) $request_id)) . '" />';
        $html .= '<input type="hidden" name="status_action" value="' . esc_attr($status_action) . '" />';
        $html .= wp_nonce_field('bso_update_hr_request', '_wpnonce', true, false);

        if ($status_action === 'approved') {
            $html .= '<input type="number" min="1" name="effective_round" value="' . esc_attr((string) $effective_round) . '" placeholder="Effective round" style="width:120px; margin-right:4px;" />';
        }

        $html .= '<input type="text" name="decision_note" value="' . esc_attr($decision_note) . '" placeholder="Decision note" style="width:190px; margin-right:4px;" />';
        $html .= '<button type="submit" class="button button-secondary">' . esc_html($label) . '</button>';
        $html .= '</form>';

        return $html;
    }

    private function render_round_status_form($game_id, $round_id, $status_action, $label) {
        $html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block; margin:0 6px 0 0;">';
        $html .= '<input type="hidden" name="action" value="bso_update_round_status" />';
        $html .= '<input type="hidden" name="game_id" value="' . esc_attr((string) ((int) $game_id)) . '" />';
        $html .= '<input type="hidden" name="round_id" value="' . esc_attr((string) ((int) $round_id)) . '" />';
        $html .= '<input type="hidden" name="status_action" value="' . esc_attr($status_action) . '" />';
        $html .= wp_nonce_field('bso_update_round_status', '_wpnonce', true, false);
        $html .= '<button type="submit" class="button button-secondary">' . esc_html($label) . '</button>';
        $html .= '</form>';

        return $html;
    }

    public function ajax_dashboard_data() {
        $requested_game_id = isset($_GET['game_id']) ? absint($_GET['game_id']) : 0;
        $context = $this->resolve_dashboard_context($requested_game_id);

        if (!$context) {
            wp_send_json_success(array(
                'html' => '<div class="bso-dashboard"><h3>Tussenstand</h3><p>Geen scoredata beschikbaar.</p></div>',
                'status' => 'empty',
                'phase' => 'T06-dashboard-scores',
                'summary' => array(),
                'standings' => array(),
                'final' => array(),
            ));
        }

        $standings_rows = $this->get_scores_for_round($context['game_id'], $context['latest_round_id']);
        $final_rows = $context['is_finalized'] ? $this->get_scores_for_round($context['game_id'], $context['latest_round_id']) : array();

        $html = '<div class="bso-dashboard">';
        $html .= '<h3>Tussenstand</h3>';
        $html .= '<p>Game #' . esc_html((string) $context['game_id']) . ' | Ronde ' . esc_html((string) $context['latest_turn_number']) . '</p>';
        $html .= $this->render_dashboard_table($standings_rows, 'tussenstand');

        if ($context['is_finalized']) {
            $html .= '<h3>Eindstand</h3>';
            $html .= '<p>De game is afgerond. Dit is de definitieve ranglijst.</p>';
            $html .= $this->render_dashboard_table($final_rows, 'eindstand');
        } else {
            $html .= '<h3>Eindstand</h3>';
            $html .= '<p>Eindstand wordt getoond zodra alle rondes zijn gesloten.</p>';
        }

        $html .= '<p><em>Laatste update: ' . esc_html(current_time('mysql')) . '</em></p>';
        $html .= '</div>';

        wp_send_json_success(array(
            'html' => $html,
            'status' => 'ok',
            'phase' => 'T06-dashboard-scores',
            'summary' => array(
                'game_id' => $context['game_id'],
                'round_id' => $context['latest_round_id'],
                'turn_number' => $context['latest_turn_number'],
                'is_finalized' => $context['is_finalized'],
            ),
            'standings' => $this->format_rows_for_response($standings_rows),
            'final' => $this->format_rows_for_response($final_rows),
        ));
    }

    private function get_commitment_record($game_id, $round_id, $organization_id) {
        global $wpdb;

        if ($game_id <= 0 || $round_id <= 0 || $organization_id <= 0) {
            return null;
        }

        $table = $wpdb->prefix . 'bso_commitments';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE game_id = %d AND round_id = %d AND organization_id = %d LIMIT 1",
                $game_id,
                $round_id,
                $organization_id
            ),
            ARRAY_A
        );
    }

    private function get_round_record($game_id) {
        global $wpdb;

        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, turn_number, status, start_datetime, end_datetime
                 FROM {$rounds_table}
                 WHERE game_id = %d
                 ORDER BY CASE status WHEN 'open' THEN 0 WHEN 'locked' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END, turn_number DESC, id DESC
                 LIMIT 1",
                $game_id
            ),
            ARRAY_A
        );
    }

    private function render_commitment_summary_block(array $context) {
        $commitment = !empty($context['commitment']) && is_array($context['commitment']) ? $context['commitment'] : array();

        if (empty($commitment)) {
            return '<div class="bso-commitment-summary"><p>Nog geen commitment opgeslagen voor deze ronde.</p></div>';
        }

        $summary = array(
            'theme' => isset($commitment['theme']) ? (string) $commitment['theme'] : '-',
            'price_jeans' => isset($commitment['price_jeans']) ? number_format_i18n((float) $commitment['price_jeans'], 2) : '0,00',
            'advertisement_total' => isset($commitment['media_total']) ? number_format_i18n((float) $commitment['media_total'], 2) : '0,00',
            'production_segment_1' => isset($commitment['production_segment_1']) ? (int) $commitment['production_segment_1'] : 0,
            'production_segment_2' => isset($commitment['production_segment_2']) ? (int) $commitment['production_segment_2'] : 0,
            'production_segment_3' => isset($commitment['production_segment_3']) ? (int) $commitment['production_segment_3'] : 0,
            'hiring_staff' => isset($commitment['hiring_staff']) ? (int) $commitment['hiring_staff'] : 0,
            'layoff_staff' => isset($commitment['layoff_staff']) ? (int) $commitment['layoff_staff'] : 0,
            'distribution_form' => isset($commitment['distribution_form']) && $commitment['distribution_form'] !== '' ? (string) $commitment['distribution_form'] : '-',
        );

        $html = '<div class="bso-commitment-summary">';
        $html .= '<p><strong>Laatste commitment</strong></p>';
        $html .= '<ul class="bso-commitment-summary__list">';
        $html .= '<li>Thema: ' . esc_html($summary['theme']) . '</li>';
        $html .= '<li>Prijs jeans: ' . esc_html($summary['price_jeans']) . '</li>';
        $html .= '<li>Media totaal: ' . esc_html($summary['advertisement_total']) . '</li>';
        $html .= '<li>Productie: ' . esc_html((string) $summary['production_segment_1']) . ' / ' . esc_html((string) $summary['production_segment_2']) . ' / ' . esc_html((string) $summary['production_segment_3']) . '</li>';
        $html .= '<li>Personeel: + ' . esc_html((string) $summary['hiring_staff']) . ' / - ' . esc_html((string) $summary['layoff_staff']) . '</li>';
        $html .= '<li>Distributie: ' . esc_html($summary['distribution_form']) . '</li>';
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    private function get_player_dashboard_context($requested_game_id = 0) {
        global $wpdb;

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return null;
        }

        $players_table = $wpdb->prefix . 'bso_players';
        $orgs_table = $wpdb->prefix . 'bso_organizations';
        $games_table = $wpdb->prefix . 'bso_games';

        $params = array($user_id);
        $game_filter = '';
        if ($requested_game_id > 0) {
            $game_filter = ' AND g.id = %d';
            $params[] = $requested_game_id;
        }

        $player = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    p.id AS player_id,
                    p.wp_user_id,
                    p.organization_id,
                    p.role_in_team,
                    p.display_name AS player_display_name,
                    o.name AS organization_name,
                    o.description AS organization_description,
                    o.status AS organization_status,
                    g.id AS game_id,
                    g.name AS game_name,
                    g.description AS game_description,
                    g.status AS game_status
                 FROM {$players_table} p
                 INNER JOIN {$orgs_table} o ON o.id = p.organization_id
                 INNER JOIN {$games_table} g ON g.id = o.game_id
                 WHERE p.wp_user_id = %d {$game_filter}
                 ORDER BY CASE g.status WHEN 'active' THEN 0 WHEN 'draft' THEN 1 WHEN 'completed' THEN 2 WHEN 'closed' THEN 3 ELSE 4 END, g.id DESC, p.id DESC
                 LIMIT 1",
                $params
            ),
            ARRAY_A
        );

        if (!$player) {
            return null;
        }

        $round = $this->get_round_record((int) $player['game_id']);
        if (!$round) {
            return null;
        }

        $commitment = $this->get_commitment_record((int) $player['game_id'], (int) $round['id'], (int) $player['organization_id']);
        $standings_rows = $this->get_scores_for_round((int) $player['game_id'], (int) $round['id']);

        return array_merge($player, array(
            'round_id' => (int) $round['id'],
            'round_turn_number' => (int) $round['turn_number'],
            'round_status' => (string) $round['status'],
            'round_start_datetime' => isset($round['start_datetime']) ? (string) $round['start_datetime'] : '',
            'round_end_datetime' => isset($round['end_datetime']) ? (string) $round['end_datetime'] : '',
            'commitment' => $commitment,
            'standings_rows' => $standings_rows,
        ));
    }

    private function resolve_dashboard_context($requested_game_id) {
        global $wpdb;

        $scores_table = $wpdb->prefix . 'bso_round_scores';
        $rounds_table = $wpdb->prefix . 'bso_game_rounds';
        $games_table = $wpdb->prefix . 'bso_games';

        $game_id = (int) $requested_game_id;
        if ($game_id <= 0) {
            $game_id = (int) $wpdb->get_var("SELECT game_id FROM {$scores_table} ORDER BY round_id DESC, id DESC LIMIT 1");
        }

        if ($game_id <= 0) {
            return null;
        }

        $latest_round_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT round_id FROM {$scores_table} WHERE game_id = %d ORDER BY round_id DESC LIMIT 1",
                $game_id
            )
        );

        if ($latest_round_id <= 0) {
            return null;
        }

        $latest_turn_number = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT turn_number FROM {$rounds_table} WHERE id = %d AND game_id = %d LIMIT 1",
                $latest_round_id,
                $game_id
            )
        );

        $open_round_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$rounds_table} WHERE game_id = %d AND status = 'open'",
                $game_id
            )
        );

        $game_status = (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM {$games_table} WHERE id = %d LIMIT 1",
                $game_id
            )
        );

        $is_finalized = ($open_round_count === 0) || in_array($game_status, array('closed', 'completed', 'finished'), true);

        return array(
            'game_id' => $game_id,
            'latest_round_id' => $latest_round_id,
            'latest_turn_number' => $latest_turn_number,
            'is_finalized' => $is_finalized,
        );
    }

    private function get_scores_for_round($game_id, $round_id) {
        global $wpdb;

        $scores_table = $wpdb->prefix . 'bso_round_scores';
        $orgs_table = $wpdb->prefix . 'bso_organizations';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    s.organization_id,
                    COALESCE(o.name, CONCAT('Organisatie #', s.organization_id)) AS organization_name,
                    s.rank_position,
                    s.turnover,
                    s.profit,
                    s.market_index,
                    s.cumulative_score
                 FROM {$scores_table} s
                 LEFT JOIN {$orgs_table} o ON o.id = s.organization_id
                 WHERE s.game_id = %d AND s.round_id = %d
                 ORDER BY
                    CASE WHEN s.rank_position IS NULL THEN 999999 ELSE s.rank_position END ASC,
                    s.cumulative_score DESC,
                    s.profit DESC",
                $game_id,
                $round_id
            ),
            ARRAY_A
        );
    }

    private function render_dashboard_table(array $rows, $table_type, $highlight_organization_id = 0) {
        if (empty($rows)) {
            return '<p>Geen scoredata beschikbaar voor deze ronde.</p>';
        }

        $html = '<table class="widefat striped bso-score-table bso-score-table-' . esc_attr($table_type) . '">';
        $html .= '<thead><tr>';
        $html .= '<th>Rank</th><th>Organisatie</th><th>Omzet</th><th>Winst</th><th>Marktaandeel</th><th>Cumulatieve score</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $rank = !empty($row['rank_position']) ? (int) $row['rank_position'] : '-';
            $row_class = ((int) $highlight_organization_id > 0 && (int) $row['organization_id'] === (int) $highlight_organization_id) ? ' class="bso-score-table__row bso-score-table__row--highlight"' : ' class="bso-score-table__row"';
            $html .= '<tr' . $row_class . '>';
            $html .= '<td>' . esc_html((string) $rank) . '</td>';
            $html .= '<td>' . esc_html((string) $row['organization_name']) . '</td>';
            $html .= '<td>' . esc_html(number_format_i18n((float) $row['turnover'], 2)) . '</td>';
            $html .= '<td>' . esc_html(number_format_i18n((float) $row['profit'], 2)) . '</td>';
            $html .= '<td>' . esc_html(number_format_i18n(((float) $row['market_index']) * 100, 2)) . '%</td>';
            $html .= '<td>' . esc_html(number_format_i18n((float) $row['cumulative_score'], 4)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function format_rows_for_response(array $rows) {
        $response = array();

        foreach ($rows as $row) {
            $response[] = array(
                'organization_id' => (int) $row['organization_id'],
                'organization_name' => (string) $row['organization_name'],
                'rank_position' => !empty($row['rank_position']) ? (int) $row['rank_position'] : null,
                'turnover' => (float) $row['turnover'],
                'profit' => (float) $row['profit'],
                'market_index' => (float) $row['market_index'],
                'cumulative_score' => (float) $row['cumulative_score'],
            );
        }

        return $response;
    }
}