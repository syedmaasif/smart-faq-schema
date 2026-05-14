<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Admin_Menu {

	public static function init() {
		add_action( 'admin_menu',            array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_settings_assets' ) );
		add_action( 'admin_post_sfaq_save_settings',       array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_sfaq_reset_colors',         array( __CLASS__, 'reset_colors' ) );
	}

	public static function register_menus() {
		add_menu_page(
			esc_html__( 'Smart FAQ Schema', 'smart-faq-schema' ),
			esc_html__( 'Smart FAQ', 'smart-faq-schema' ),
			'manage_options',
			'smart-faq-schema',
			array( __CLASS__, 'render_dashboard' ),
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="white" d="M10 2a8 8 0 100 16A8 8 0 0010 2zm1 11H9v-2h2v2zm0-4H9V7h2v2z"/></svg>' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Safe: encoding inline SVG icon only.
			58
		);

		add_submenu_page( 'smart-faq-schema', esc_html__( 'Dashboard', 'smart-faq-schema' ),          esc_html__( 'Dashboard', 'smart-faq-schema' ),          'manage_options', 'smart-faq-schema',     array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'smart-faq-schema', esc_html__( 'Appearance & Style', 'smart-faq-schema' ), esc_html__( 'Appearance & Style', 'smart-faq-schema' ), 'manage_options', 'sfaq-appearance',       array( __CLASS__, 'render_appearance' ) );
		add_submenu_page( 'smart-faq-schema', esc_html__( 'Schema Settings', 'smart-faq-schema' ),    esc_html__( 'Schema Settings', 'smart-faq-schema' ),    'manage_options', 'sfaq-schema',            array( __CLASS__, 'render_schema_settings' ) );
		add_submenu_page( 'smart-faq-schema', esc_html__( 'Advanced Settings', 'smart-faq-schema' ),  esc_html__( 'Advanced', 'smart-faq-schema' ),           'manage_options', 'sfaq-advanced',          array( __CLASS__, 'render_advanced' ) );
		add_submenu_page( 'smart-faq-schema', esc_html__( 'Bulk Manager', 'smart-faq-schema' ),        esc_html__( 'Bulk Manager', 'smart-faq-schema' ),       'manage_options', 'sfaq-bulk-manager',      array( 'SFAQ_Bulk_Manager', 'render_page' ) );
		add_submenu_page( 'smart-faq-schema', esc_html__( 'Guide & Help', 'smart-faq-schema' ),        esc_html__( 'Guide & Help', 'smart-faq-schema' ),       'manage_options', 'sfaq-guide',             array( __CLASS__, 'render_guide' ) );
	}

	public static function enqueue_settings_assets( $hook ) {
		if ( false === strpos( $hook, 'smart-faq' ) && false === strpos( $hook, 'sfaq' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'sfaq-admin',
			SFAQ_PLUGIN_URL . 'admin/css/sfaq-admin.css',
			array( 'wp-color-picker' ),
			SFAQ_VERSION
		);
		wp_enqueue_script(
			'sfaq-admin',
			SFAQ_PLUGIN_URL . 'admin/js/sfaq-admin.js',
			array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
			SFAQ_VERSION,
			true
		);
		wp_localize_script( 'sfaq-admin', 'sfaqAdmin', array(
			'nonce'       => wp_create_nonce( 'sfaq_admin_nonce' ),
			'confirm_del' => esc_html__( 'Remove this FAQ?', 'smart-faq-schema' ),
		) );
	}

	/**
	 * Save all settings via admin_post action.
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'smart-faq-schema' ) );
		}
		check_admin_referer( 'sfaq_save_settings' );

		// Fields that accept any text value (sanitize_text_field)
		$text_fields = array(
			'ui_style', 'global_title', 'display_method', 'schema_type',
			'color_q_text', 'color_q_bg', 'color_q_border',
			'color_q_active_bg', 'color_q_active_text',
			'color_q_hover_bg', 'color_q_hover_text',
			'color_a_text', 'color_a_bg',
			'color_icon', 'color_icon_active',
			'font_family', 'font_size_q', 'font_size_a',
			'font_weight_q', 'border_radius',
		);

		// Checkbox fields (absent = off)
		$checkbox_fields = array( 'show_section_title', 'schema_enabled' );

		// Long-text fields (wp_kses_post)
		$longtext_fields = array( 'custom_schema' );

		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ 'sfaq_' . $field ] ) ) {
				update_option( 'sfaq_' . $field, sanitize_text_field( wp_unslash( $_POST[ 'sfaq_' . $field ] ) ) );
			}
		}

		foreach ( $checkbox_fields as $field ) {
			update_option( 'sfaq_' . $field, isset( $_POST[ 'sfaq_' . $field ] ) ? '1' : '0' );
		}

		foreach ( $longtext_fields as $field ) {
			if ( isset( $_POST[ 'sfaq_' . $field ] ) ) {
				update_option( 'sfaq_' . $field, wp_kses_post( wp_unslash( $_POST[ 'sfaq_' . $field ] ) ) );
			}
		}

		SFAQ_Cache::flush();

		$redirect = isset( $_POST['_redirect'] )
			? sanitize_text_field( wp_unslash( $_POST['_redirect'] ) )
			: admin_url( 'admin.php?page=smart-faq-schema' );

		wp_safe_redirect( add_query_arg( 'sfaq_saved', '1', $redirect ) );
		exit;
	}

	/* ============================
	   PAGE RENDERS
	   ============================ */

	public static function render_dashboard() {
		$total_with_faq = self::count_posts_with_faq();
		$post_count     = wp_count_posts( 'post' );
		$page_count     = wp_count_posts( 'page' );
		$total_posts    = (int) $post_count->publish + (int) $page_count->publish;
		$without_faq    = max( 0, $total_posts - $total_with_faq );
		?>
		<div class="sfaq-admin-page wrap">
			<?php self::render_settings_notice(); ?>
			<div class="sfaq-page-header">
				<div class="sfaq-page-header-inner">
					<div class="sfaq-logo-area">
						<div class="sfaq-logo-icon">❓</div>
						<div>
							<h1 class="sfaq-page-title"><?php esc_html_e( 'Smart FAQ Schema', 'smart-faq-schema' ); ?></h1>
							<p class="sfaq-version-badge">v<?php echo esc_html( SFAQ_VERSION ); ?></p>
						</div>
					</div>
					<p class="sfaq-tagline"><?php esc_html_e( 'Beautiful FAQ sections with FAQPage schema for Google Rich Results. Elementor-safe. Lightning fast.', 'smart-faq-schema' ); ?></p>
				</div>
			</div>

			<!-- Stats -->
			<div class="sfaq-stats-row">
				<div class="sfaq-stat-card">
					<div class="sfaq-stat-icon">📝</div>
					<div class="sfaq-stat-value"><?php echo intval( $total_posts ); ?></div>
					<div class="sfaq-stat-label"><?php esc_html_e( 'Total Published', 'smart-faq-schema' ); ?></div>
				</div>
				<div class="sfaq-stat-card sfaq-stat-highlight">
					<div class="sfaq-stat-icon">✅</div>
					<div class="sfaq-stat-value"><?php echo intval( $total_with_faq ); ?></div>
					<div class="sfaq-stat-label"><?php esc_html_e( 'With FAQs', 'smart-faq-schema' ); ?></div>
				</div>
				<div class="sfaq-stat-card">
					<div class="sfaq-stat-icon">❌</div>
					<div class="sfaq-stat-value"><?php echo intval( $without_faq ); ?></div>
					<div class="sfaq-stat-label"><?php esc_html_e( 'Without FAQs', 'smart-faq-schema' ); ?></div>
				</div>
				<div class="sfaq-stat-card">
					<div class="sfaq-stat-icon">🎨</div>
					<div class="sfaq-stat-value"><?php echo esc_html( strtoupper( SFAQ_Settings::get( 'ui_style', 'style1' ) ) ); ?></div>
					<div class="sfaq-stat-label"><?php esc_html_e( 'Active Style', 'smart-faq-schema' ); ?></div>
				</div>
			</div>

			<!-- Quick Actions -->
			<div class="sfaq-quick-actions">
				<h2><?php esc_html_e( 'Quick Actions', 'smart-faq-schema' ); ?></h2>
				<div class="sfaq-action-grid">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sfaq-bulk-manager' ) ); ?>" class="sfaq-action-card">
						<span class="sfaq-action-icon">📋</span>
						<strong><?php esc_html_e( 'Bulk Manager', 'smart-faq-schema' ); ?></strong>
						<span><?php esc_html_e( 'See all posts & add FAQs', 'smart-faq-schema' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sfaq-appearance' ) ); ?>" class="sfaq-action-card">
						<span class="sfaq-action-icon">🎨</span>
						<strong><?php esc_html_e( 'Appearance', 'smart-faq-schema' ); ?></strong>
						<span><?php esc_html_e( 'Choose UI style & colors', 'smart-faq-schema' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sfaq-schema' ) ); ?>" class="sfaq-action-card">
						<span class="sfaq-action-icon">🔖</span>
						<strong><?php esc_html_e( 'Schema Settings', 'smart-faq-schema' ); ?></strong>
						<span><?php esc_html_e( 'Configure FAQPage schema', 'smart-faq-schema' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sfaq-guide' ) ); ?>" class="sfaq-action-card">
						<span class="sfaq-action-icon">📖</span>
						<strong><?php esc_html_e( 'Guide', 'smart-faq-schema' ); ?></strong>
						<span><?php esc_html_e( 'How to use this plugin', 'smart-faq-schema' ); ?></span>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_appearance() {
		$current_style = SFAQ_Settings::get( 'ui_style', 'style1' );
		$styles = array(
			'style1' => array( 'name' => __( 'Classic Accordion', 'smart-faq-schema' ),  'desc' => __( 'Clean +/− toggle, red active state, rounded corners. The default.', 'smart-faq-schema' ) ),
			'style2' => array( 'name' => __( 'Card Style', 'smart-faq-schema' ),          'desc' => __( 'Each FAQ is a raised card with shadow. Modern and spacious.', 'smart-faq-schema' ) ),
			'style3' => array( 'name' => __( 'Minimal Line', 'smart-faq-schema' ),        'desc' => __( 'Borderless with only a bottom line separator. Ultra-clean.', 'smart-faq-schema' ) ),
			'style4' => array( 'name' => __( 'Bold Left Border', 'smart-faq-schema' ),    'desc' => __( 'Thick colored left border accent. Strong visual hierarchy.', 'smart-faq-schema' ) ),
			'style5' => array( 'name' => __( 'Numbered Steps', 'smart-faq-schema' ),      'desc' => __( 'Each FAQ is numbered. Great for how-to style FAQs.', 'smart-faq-schema' ) ),
		);

		$color_defaults = array(
			'color_q_text'        => '#111111',
			'color_q_bg'          => '#ffffff',
			'color_q_border'      => '#dddddd',
			'color_q_hover_bg'    => '#f5f5f5',
			'color_q_hover_text'  => '#111111',
			'color_q_active_bg'   => '#cc0000',
			'color_q_active_text' => '#ffffff',
			'color_icon'          => '#cc0000',
			'color_icon_active'   => '#ffffff',
			'color_a_text'        => '#333333',
			'color_a_bg'          => '#ffffff',
		);

		$color_labels = array(
			'color_q_text'        => __( 'Question Text Color', 'smart-faq-schema' ),
			'color_q_bg'          => __( 'Question Background', 'smart-faq-schema' ),
			'color_q_border'      => __( 'Question Border Color', 'smart-faq-schema' ),
			'color_q_hover_bg'    => __( 'Question Hover Background', 'smart-faq-schema' ),
			'color_q_hover_text'  => __( 'Question Hover Text', 'smart-faq-schema' ),
			'color_q_active_bg'   => __( 'Active Question Background', 'smart-faq-schema' ),
			'color_q_active_text' => __( 'Active Question Text', 'smart-faq-schema' ),
			'color_icon'          => __( 'Icon Color', 'smart-faq-schema' ),
			'color_icon_active'   => __( 'Icon Color (Active)', 'smart-faq-schema' ),
			'color_a_text'        => __( 'Answer Text Color', 'smart-faq-schema' ),
			'color_a_bg'          => __( 'Answer Background', 'smart-faq-schema' ),
		);
		?>
		<div class="sfaq-admin-page wrap">
			<?php self::render_settings_notice(); ?>
			<div class="sfaq-page-header">
				<h1 class="sfaq-page-title">🎨 <?php esc_html_e( 'Appearance & Style', 'smart-faq-schema' ); ?></h1>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sfaq_save_settings' ); ?>
				<input type="hidden" name="action" value="sfaq_save_settings">
				<input type="hidden" name="_redirect" value="<?php echo esc_attr( admin_url( 'admin.php?page=sfaq-appearance' ) ); ?>">

				<!-- UI Style Selector -->
				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'FAQ Style', 'smart-faq-schema' ); ?></h2>
					<p class="sfaq-section-desc"><?php esc_html_e( 'Choose the visual style for your FAQ sections. All styles are mobile-optimized.', 'smart-faq-schema' ); ?></p>
					<div class="sfaq-style-grid">
						<?php foreach ( $styles as $key => $style ) : ?>
						<label class="sfaq-style-card <?php echo ( $current_style === $key ) ? 'sfaq-style-active' : ''; ?>">
							<input type="radio" name="sfaq_ui_style" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_style, $key ); ?> class="sfaq-radio-hidden">
							<div class="sfaq-style-preview sfaq-preview-<?php echo esc_attr( $key ); ?>">
								<div class="sfaq-preview-item">
									<div class="sfaq-preview-q"><?php esc_html_e( 'Question text here', 'smart-faq-schema' ); ?></div>
									<div class="sfaq-preview-a"><?php esc_html_e( 'Answer text here', 'smart-faq-schema' ); ?></div>
								</div>
								<div class="sfaq-preview-item sfaq-preview-closed">
									<div class="sfaq-preview-q"><?php esc_html_e( 'Another question', 'smart-faq-schema' ); ?></div>
								</div>
							</div>
							<div class="sfaq-style-info">
								<strong><?php echo esc_html( $style['name'] ); ?></strong>
								<span><?php echo esc_html( $style['desc'] ); ?></span>
							</div>
							<div class="sfaq-style-check">&#10003;</div>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Colors -->
				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'Colors', 'smart-faq-schema' ); ?></h2>
					<p class="sfaq-section-desc"><?php esc_html_e( 'These apply globally to all FAQ sections on your site.', 'smart-faq-schema' ); ?></p>
					<div class="sfaq-color-grid">
						<?php foreach ( $color_labels as $key => $label ) :
							$val = SFAQ_Settings::get( $key, $color_defaults[ $key ] );
						?>
						<div class="sfaq-color-field">
							<label class="sfaq-color-label"><?php echo esc_html( $label ); ?></label>
							<input type="text"
								   name="sfaq_<?php echo esc_attr( $key ); ?>"
								   value="<?php echo esc_attr( $val ); ?>"
								   class="sfaq-color-picker"
								   data-default-color="<?php echo esc_attr( $color_defaults[ $key ] ); ?>">
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Typography -->
				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'Typography', 'smart-faq-schema' ); ?></h2>
					<div class="sfaq-settings-grid-3">
						<div class="sfaq-field-group">
							<label class="sfaq-label"><?php esc_html_e( 'Font Family', 'smart-faq-schema' ); ?></label>
							<select name="sfaq_font_family" class="sfaq-select">
								<?php
								$fonts = array(
									'inherit'                        => __( 'Inherit from Theme', 'smart-faq-schema' ),
									'Arial, sans-serif'              => 'Arial',
									'Georgia, serif'                 => 'Georgia',
									"'Times New Roman', serif"       => 'Times New Roman',
									"'Trebuchet MS', sans-serif"     => 'Trebuchet MS',
									'Verdana, sans-serif'            => 'Verdana',
									"'Courier New', monospace"       => 'Courier New',
									"'Segoe UI', Tahoma, sans-serif" => 'Segoe UI',
								);
								$cur = SFAQ_Settings::get( 'font_family', 'inherit' );
								foreach ( $fonts as $val => $label ) :
								?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $cur, $val ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="sfaq-field-group">
							<label class="sfaq-label"><?php esc_html_e( 'Question Font Size (px)', 'smart-faq-schema' ); ?></label>
							<input type="number" name="sfaq_font_size_q" value="<?php echo esc_attr( SFAQ_Settings::get( 'font_size_q', '16' ) ); ?>" min="10" max="36" class="sfaq-number-input">
						</div>
						<div class="sfaq-field-group">
							<label class="sfaq-label"><?php esc_html_e( 'Answer Font Size (px)', 'smart-faq-schema' ); ?></label>
							<input type="number" name="sfaq_font_size_a" value="<?php echo esc_attr( SFAQ_Settings::get( 'font_size_a', '15' ) ); ?>" min="10" max="32" class="sfaq-number-input">
						</div>
						<div class="sfaq-field-group">
							<label class="sfaq-label"><?php esc_html_e( 'Question Font Weight', 'smart-faq-schema' ); ?></label>
							<select name="sfaq_font_weight_q" class="sfaq-select">
								<?php
								$weights = array( '400' => 'Normal (400)', '500' => 'Medium (500)', '600' => 'Semi-Bold (600)', '700' => 'Bold (700)', '800' => 'Extra Bold (800)' );
								$cw = SFAQ_Settings::get( 'font_weight_q', '600' );
								foreach ( $weights as $val => $label ) :
								?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $cw, $val ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="sfaq-field-group">
							<label class="sfaq-label"><?php esc_html_e( 'Border Radius (px)', 'smart-faq-schema' ); ?></label>
							<input type="number" name="sfaq_border_radius" value="<?php echo esc_attr( SFAQ_Settings::get( 'border_radius', '10' ) ); ?>" min="0" max="30" class="sfaq-number-input">
						</div>
					</div>
				</div>

				<!-- Section Title -->
				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'Section Title', 'smart-faq-schema' ); ?></h2>
					<div class="sfaq-settings-grid-2">
						<div class="sfaq-field-group">
							<label class="sfaq-label"><?php esc_html_e( 'Global Section Title (H2)', 'smart-faq-schema' ); ?></label>
							<input type="text" name="sfaq_global_title" value="<?php echo esc_attr( SFAQ_Settings::get( 'global_title', 'Frequently Asked Questions' ) ); ?>" class="sfaq-text-input" placeholder="Frequently Asked Questions">
							<p class="sfaq-help-text"><?php esc_html_e( 'Can be overridden per post in the FAQ meta box.', 'smart-faq-schema' ); ?></p>
						</div>
						<div class="sfaq-field-group">
							<label class="sfaq-toggle-label">
								<input type="checkbox" name="sfaq_show_section_title" value="1" <?php checked( SFAQ_Settings::get( 'show_section_title', '1' ), '1' ); ?> class="sfaq-toggle-input">
								<span class="sfaq-toggle-switch"></span>
								<span class="sfaq-toggle-text"><?php esc_html_e( 'Show Section Title', 'smart-faq-schema' ); ?></span>
							</label>
							<p class="sfaq-help-text"><?php esc_html_e( 'Toggle the H2 heading above the accordion.', 'smart-faq-schema' ); ?></p>
						</div>
					</div>
				</div>

				<div class="sfaq-submit-row">
					<button type="submit" class="sfaq-btn sfaq-btn-primary sfaq-btn-large">
						&#128190; <?php esc_html_e( 'Save Appearance Settings', 'smart-faq-schema' ); ?>
					</button>
					<span class="sfaq-save-note"><?php esc_html_e( 'Cache will be cleared automatically on save.', 'smart-faq-schema' ); ?></span>
				</div>
			</form>

			<!-- Reset to Defaults form (separate form, no nonce conflict) -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sfaq-reset-form" onsubmit="return confirm('<?php esc_attr_e( 'Reset all colors and typography to plugin defaults? This cannot be undone.', 'smart-faq-schema' ); ?>');">
				<?php wp_nonce_field( 'sfaq_reset_colors' ); ?>
				<input type="hidden" name="action" value="sfaq_reset_colors">
				<input type="hidden" name="_redirect" value="<?php echo esc_attr( admin_url( 'admin.php?page=sfaq-appearance' ) ); ?>">
				<div class="sfaq-reset-row">
					<button type="submit" class="sfaq-btn sfaq-btn-ghost sfaq-btn-large">
						&#8635; <?php esc_html_e( 'Reset Colors & Typography to Defaults', 'smart-faq-schema' ); ?>
					</button>
					<span class="sfaq-save-note"><?php esc_html_e( 'Resets all colors, fonts and border radius to the plugin defaults.', 'smart-faq-schema' ); ?></span>
				</div>
			</form>
		</div>
		<?php
	}

	public static function render_schema_settings() {
		$schema_type    = SFAQ_Settings::get( 'schema_type', 'standard' );
		$custom_schema  = SFAQ_Settings::get( 'custom_schema', '' );
		$schema_enabled = SFAQ_Settings::get( 'schema_enabled', '1' );

		$example_custom = '{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": "{{faq_items}}"
}';
		?>
		<div class="sfaq-admin-page wrap">
			<?php self::render_settings_notice(); ?>
			<div class="sfaq-page-header">
				<h1 class="sfaq-page-title">&#128278; <?php esc_html_e( 'Schema Settings', 'smart-faq-schema' ); ?></h1>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sfaq_save_settings' ); ?>
				<input type="hidden" name="action" value="sfaq_save_settings">
				<input type="hidden" name="_redirect" value="<?php echo esc_attr( admin_url( 'admin.php?page=sfaq-schema' ) ); ?>">

				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'Global Schema Toggle', 'smart-faq-schema' ); ?></h2>
					<label class="sfaq-toggle-label">
						<input type="checkbox" name="sfaq_schema_enabled" value="1" <?php checked( $schema_enabled, '1' ); ?> class="sfaq-toggle-input">
						<span class="sfaq-toggle-switch"></span>
						<span class="sfaq-toggle-text"><?php esc_html_e( 'Enable FAQPage Schema Globally', 'smart-faq-schema' ); ?></span>
					</label>
					<p class="sfaq-help-text"><?php esc_html_e( 'Master switch. Individual posts can also override this. Schema is output in <head> as JSON-LD.', 'smart-faq-schema' ); ?></p>
				</div>

				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'Schema Type', 'smart-faq-schema' ); ?></h2>
					<div class="sfaq-radio-group">
						<label class="sfaq-radio-option <?php echo ( 'standard' === $schema_type ) ? 'sfaq-radio-active' : ''; ?>">
							<input type="radio" name="sfaq_schema_type" value="standard" <?php checked( $schema_type, 'standard' ); ?>>
							<div>
								<strong><?php esc_html_e( 'Standard FAQPage Schema', 'smart-faq-schema' ); ?></strong>
								<p><?php esc_html_e( 'Recommended by Google. Uses FAQPage @type with mainEntity array. Eligible for FAQ rich results in search.', 'smart-faq-schema' ); ?></p>
								<div class="sfaq-schema-example">
<pre>{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Your Question Here",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Your answer here."
      }
    }
  ]
}</pre>
								</div>
							</div>
						</label>
						<label class="sfaq-radio-option <?php echo ( 'custom' === $schema_type ) ? 'sfaq-radio-active' : ''; ?>">
							<input type="radio" name="sfaq_schema_type" value="custom" <?php checked( $schema_type, 'custom' ); ?>>
							<div>
								<strong><?php esc_html_e( 'Custom Schema Template', 'smart-faq-schema' ); ?></strong>
								<p><?php esc_html_e( 'Define your own JSON-LD template. Use dynamic placeholders that are replaced automatically.', 'smart-faq-schema' ); ?></p>
								<div class="sfaq-info-box">
									<strong><?php esc_html_e( 'Available Placeholders:', 'smart-faq-schema' ); ?></strong>
									<ul>
										<li><code>{{faq_items}}</code> &mdash; <?php esc_html_e( 'Replaced with the full FAQ questions/answers array', 'smart-faq-schema' ); ?></li>
										<li><code>{{post_url}}</code> &mdash; <?php esc_html_e( 'The current post\'s URL', 'smart-faq-schema' ); ?></li>
										<li><code>{{post_title}}</code> &mdash; <?php esc_html_e( 'The current post\'s title', 'smart-faq-schema' ); ?></li>
										<li><code>{{site_name}}</code> &mdash; <?php esc_html_e( 'Your site name', 'smart-faq-schema' ); ?></li>
									</ul>
								</div>
							</div>
						</label>
					</div>

					<div class="sfaq-custom-schema-wrap" id="sfaq-custom-schema-wrap" <?php echo ( 'custom' !== $schema_type ) ? 'style="display:none"' : ''; ?>>
						<label class="sfaq-label"><?php esc_html_e( 'Custom Schema Template (JSON-LD)', 'smart-faq-schema' ); ?></label>
						<textarea name="sfaq_custom_schema" rows="15" class="sfaq-code-textarea"><?php echo esc_textarea( $custom_schema ? $custom_schema : $example_custom ); ?></textarea>
						<p class="sfaq-help-text"><?php esc_html_e( 'Must be valid JSON. Falls back to standard schema if your JSON is invalid.', 'smart-faq-schema' ); ?></p>
					</div>
				</div>

				<div class="sfaq-submit-row">
					<button type="submit" class="sfaq-btn sfaq-btn-primary sfaq-btn-large">&#128190; <?php esc_html_e( 'Save Schema Settings', 'smart-faq-schema' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	public static function render_advanced() {
		$display_method = SFAQ_Settings::get( 'display_method', 'shortcode' );
		?>
		<div class="sfaq-admin-page wrap">
			<?php self::render_settings_notice(); ?>
			<div class="sfaq-page-header">
				<h1 class="sfaq-page-title">&#9881; <?php esc_html_e( 'Advanced Settings', 'smart-faq-schema' ); ?></h1>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sfaq_save_settings' ); ?>
				<input type="hidden" name="action" value="sfaq_save_settings">
				<input type="hidden" name="_redirect" value="<?php echo esc_attr( admin_url( 'admin.php?page=sfaq-advanced' ) ); ?>">

				<div class="sfaq-settings-section">
					<h2 class="sfaq-section-heading"><?php esc_html_e( 'Display Method', 'smart-faq-schema' ); ?></h2>
					<p class="sfaq-section-desc"><?php esc_html_e( 'How FAQs are inserted into your pages. Applies globally.', 'smart-faq-schema' ); ?></p>
					<div class="sfaq-radio-group">
						<label class="sfaq-radio-option sfaq-radio-option-sm <?php echo ( 'shortcode' === $display_method ) ? 'sfaq-radio-active' : ''; ?>">
							<input type="radio" name="sfaq_display_method" value="shortcode" <?php checked( $display_method, 'shortcode' ); ?>>
							<div>
								<strong><?php esc_html_e( 'Shortcode (Recommended)', 'smart-faq-schema' ); ?></strong>
								<p><?php esc_html_e( 'Paste [smart_faq] or [smart_faq id="POST_ID"] wherever you want FAQs — inside Elementor, Classic Editor, or Gutenberg.', 'smart-faq-schema' ); ?></p>
							</div>
						</label>
						<label class="sfaq-radio-option sfaq-radio-option-sm <?php echo ( 'auto' === $display_method ) ? 'sfaq-radio-active' : ''; ?>">
							<input type="radio" name="sfaq_display_method" value="auto" <?php checked( $display_method, 'auto' ); ?>>
							<div>
								<strong><?php esc_html_e( 'Auto-Append After Content', 'smart-faq-schema' ); ?></strong>
								<p><?php esc_html_e( 'FAQs automatically appear after post content on every post/page that has FAQs. No shortcode needed.', 'smart-faq-schema' ); ?></p>
								<div class="sfaq-info-box sfaq-info-box-warning">
									&#9888; <?php esc_html_e( 'Note: Auto-append uses the_content filter at priority 99. Safe with Elementor as it only fires on the main query loop, but Shortcode mode is more predictable with page builders.', 'smart-faq-schema' ); ?>
								</div>
							</div>
						</label>
					</div>
				</div>

				<div class="sfaq-submit-row">
					<button type="submit" class="sfaq-btn sfaq-btn-primary sfaq-btn-large">&#128190; <?php esc_html_e( 'Save Advanced Settings', 'smart-faq-schema' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	public static function render_guide() {
		?>
		<div class="sfaq-admin-page wrap">
			<div class="sfaq-page-header">
				<h1 class="sfaq-page-title">&#128218; <?php esc_html_e( 'Guide & Help', 'smart-faq-schema' ); ?></h1>
			</div>
			<div class="sfaq-guide-content">

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">1</div>
					<div>
						<h2><?php esc_html_e( 'Open Any Post or Page', 'smart-faq-schema' ); ?></h2>
						<p><?php esc_html_e( 'Go to Posts → Add New (or edit an existing one). Scroll below the editor and you\'ll find the "Smart FAQ Schema" meta box — just like Rank Math\'s panel.', 'smart-faq-schema' ); ?></p>
						<div class="sfaq-guide-tip">&#128161; <?php esc_html_e( 'Works in Classic Editor, Gutenberg (Block Editor), and with Elementor.', 'smart-faq-schema' ); ?></div>
					</div>
				</div>

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">2</div>
					<div>
						<h2><?php esc_html_e( 'Add Your FAQs', 'smart-faq-schema' ); ?></h2>
						<p><?php esc_html_e( 'Click "Add FAQ" to add a question + answer pair. Add as many as needed. Drag the ⠿ handle to reorder them.', 'smart-faq-schema' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Question — displayed as H3 (SEO-friendly)', 'smart-faq-schema' ); ?></li>
							<li><?php esc_html_e( 'Answer — displayed as a paragraph', 'smart-faq-schema' ); ?></li>
						</ul>
						<div class="sfaq-guide-tip">&#128161; <?php esc_html_e( 'If no FAQs are filled, nothing appears on the frontend — your existing posts are safe.', 'smart-faq-schema' ); ?></div>
					</div>
				</div>

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">3</div>
					<div>
						<h2><?php esc_html_e( 'Use the Toggles', 'smart-faq-schema' ); ?></h2>
						<ul>
							<li><strong><?php esc_html_e( 'Show FAQs', 'smart-faq-schema' ); ?></strong> &mdash; <?php esc_html_e( 'Turn off to hide the visual FAQ section but still keep schema active (great for hidden schema).', 'smart-faq-schema' ); ?></li>
							<li><strong><?php esc_html_e( 'Generate Schema', 'smart-faq-schema' ); ?></strong> &mdash; <?php esc_html_e( 'Turn off to skip JSON-LD for this specific post.', 'smart-faq-schema' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">4</div>
					<div>
						<h2><?php esc_html_e( 'Place the Shortcode (Elementor Users)', 'smart-faq-schema' ); ?></h2>
						<p><?php esc_html_e( 'Copy the shortcode from the meta box and paste it in Elementor using a Shortcode widget:', 'smart-faq-schema' ); ?></p>
						<div class="sfaq-code-block"><code>[smart_faq id="YOUR_POST_ID"]</code></div>
						<p><?php esc_html_e( 'Place it after your post-content section. The id attribute ensures it always loads the correct post\'s FAQs.', 'smart-faq-schema' ); ?></p>
						<div class="sfaq-guide-tip">&#128161; <?php esc_html_e( 'Alternatively, switch to Auto-Append mode in Advanced Settings — no shortcode needed.', 'smart-faq-schema' ); ?></div>
					</div>
				</div>

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">5</div>
					<div>
						<h2><?php esc_html_e( 'Customize Appearance', 'smart-faq-schema' ); ?></h2>
						<p><?php esc_html_e( 'Go to Smart FAQ → Appearance & Style to pick one of 5 styles, set colors, font family, size, and weight.', 'smart-faq-schema' ); ?></p>
						<div class="sfaq-guide-tip">&#128161; <?php esc_html_e( 'Cache is cleared automatically on every save.', 'smart-faq-schema' ); ?></div>
					</div>
				</div>

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">6</div>
					<div>
						<h2><?php esc_html_e( 'Use the Bulk Manager', 'smart-faq-schema' ); ?></h2>
						<p><?php esc_html_e( 'Go to Smart FAQ → Bulk Manager to see all your posts with their FAQ status. Filter by: All, Has FAQs, No FAQs. Click "Add FAQs" on any post to jump to its edit page.', 'smart-faq-schema' ); ?></p>
					</div>
				</div>

				<div class="sfaq-guide-step">
					<div class="sfaq-guide-num">7</div>
					<div>
						<h2><?php esc_html_e( 'Test Your Schema', 'smart-faq-schema' ); ?></h2>
						<p><?php esc_html_e( 'Visit Google\'s Rich Results Test and enter your post URL to verify FAQ schema is working correctly.', 'smart-faq-schema' ); ?></p>
						<div class="sfaq-code-block"><code>https://search.google.com/test/rich-results</code></div>
						<div class="sfaq-guide-tip">&#9888; <?php esc_html_e( 'Google requires both a question AND an answer for schema to be valid. Entries with only a question are excluded from schema output.', 'smart-faq-schema' ); ?></div>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/* ============================
	   HELPERS
	   ============================ */

	private static function render_settings_notice() {
		if ( isset( $_GET['sfaq_saved'] ) && '1' === sanitize_key( $_GET['sfaq_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success flag set by wp_safe_redirect after nonce-verified save.
			echo '<div class="sfaq-notice sfaq-notice-success">&#10004; ' . esc_html__( 'Settings saved and cache cleared.', 'smart-faq-schema' ) . '</div>';
		}
		if ( isset( $_GET['sfaq_reset'] ) && '1' === sanitize_key( $_GET['sfaq_reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success flag set by wp_safe_redirect after nonce-verified reset.
			echo '<div class="sfaq-notice sfaq-notice-reset">&#8635; ' . esc_html__( 'Colors and typography reset to defaults. Cache cleared.', 'smart-faq-schema' ) . '</div>';
		}
	}

	/**
	 * Count all posts that have at least one FAQ saved.
	 * Uses wp_cache_get/set (object cache) around the direct DB call, with a
	 * transient as a persistent fallback for sites without a persistent object cache.
	 */
	private static function count_posts_with_faq() {
		$cache_key  = 'sfaq_posts_with_faq_count';
		$cache_group = 'smart_faq_schema';

		// 1. Check object cache first (satisfies NoCaching requirement)
		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		// 2. Fall back to transient (persistent cache for sites without object cache)
		$transient = get_transient( $cache_key );
		if ( false !== $transient ) {
			wp_cache_set( $cache_key, $transient, $cache_group, HOUR_IN_SECONDS );
			return (int) $transient;
		}

		// 3. Query the database
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching handled above with wp_cache_get/set and transient.
		$count = $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_sfaq_faqs'"
		);

		$count = (int) $count;

		// Store in both object cache and transient
		wp_cache_set( $cache_key, $count, $cache_group, HOUR_IN_SECONDS );
		set_transient( $cache_key, $count, HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Reset all color/typography options to plugin defaults.
	 */
	public static function reset_colors() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'smart-faq-schema' ) );
		}
		check_admin_referer( 'sfaq_reset_colors' );

		$defaults = array(
			'color_q_text'        => '#111111',
			'color_q_bg'          => '#ffffff',
			'color_q_border'      => '#dddddd',
			'color_q_active_bg'   => '#cc0000',
			'color_q_active_text' => '#ffffff',
			'color_q_hover_bg'    => '#f5f5f5',
			'color_q_hover_text'  => '#111111',
			'color_a_text'        => '#333333',
			'color_a_bg'          => '#ffffff',
			'color_icon'          => '#cc0000',
			'color_icon_active'   => '#ffffff',
			'font_family'         => 'inherit',
			'font_size_q'         => '16',
			'font_size_a'         => '15',
			'font_weight_q'       => '600',
			'border_radius'       => '10',
		);

		foreach ( $defaults as $key => $value ) {
			update_option( 'sfaq_' . $key, $value );
		}

		SFAQ_Cache::flush();

		$redirect = isset( $_POST['_redirect'] )
			? sanitize_text_field( wp_unslash( $_POST['_redirect'] ) )
			: admin_url( 'admin.php?page=sfaq-appearance' );

		wp_safe_redirect( add_query_arg( 'sfaq_reset', '1', $redirect ) );
		exit;
	}

}