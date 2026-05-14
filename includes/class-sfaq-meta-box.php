<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Meta_Box {

	public static function init() {
		add_action( 'add_meta_boxes',        array( __CLASS__, 'register' ) );
		add_action( 'save_post',             array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		// REST API save support (Block Editor / Gutenberg)
		add_action( 'rest_after_insert_post', array( __CLASS__, 'save_from_rest' ), 10, 2 );
		add_action( 'rest_after_insert_page', array( __CLASS__, 'save_from_rest' ), 10, 2 );
	}

	/**
	 * Register meta box for all supported post types.
	 */
	public static function register() {
		$post_types = apply_filters( 'sfaq_post_types', array( 'post', 'page' ) );
		if ( post_type_exists( 'product' ) ) {
			$post_types[] = 'product';
		}
		foreach ( $post_types as $pt ) {
			add_meta_box(
				'sfaq_meta_box',
				'<span class="sfaq-meta-box-title"><span class="sfaq-icon">❓</span> Smart FAQ Schema</span>',
				array( __CLASS__, 'render' ),
				$pt,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Enqueue admin JS/CSS only on post edit screens.
	 */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_style(
			'sfaq-admin',
			SFAQ_PLUGIN_URL . 'admin/css/sfaq-admin.css',
			array(),
			SFAQ_VERSION
		);
		wp_enqueue_script(
			'sfaq-admin',
			SFAQ_PLUGIN_URL . 'admin/js/sfaq-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			SFAQ_VERSION,
			true
		);
		wp_localize_script( 'sfaq-admin', 'sfaqAdmin', array(
			'nonce'       => wp_create_nonce( 'sfaq_admin_nonce' ),
			'confirm_del' => esc_html__( 'Remove this FAQ?', 'smart-faq-schema' ),
			'max_faqs'    => apply_filters( 'sfaq_max_faqs', 20 ),
			'strings'     => array(
				'add_faq'     => esc_html__( 'Add FAQ', 'smart-faq-schema' ),
				'question_ph' => esc_html__( 'Enter question here...', 'smart-faq-schema' ),
				'answer_ph'   => esc_html__( 'Enter answer here...', 'smart-faq-schema' ),
				'drag_tip'    => esc_html__( 'Drag to reorder', 'smart-faq-schema' ),
			),
		) );
	}

	/**
	 * Render the meta box HTML.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'sfaq_save_' . $post->ID, 'sfaq_nonce' );

		$faqs         = get_post_meta( $post->ID, '_sfaq_faqs', true );
		$faqs         = is_array( $faqs ) ? $faqs : array();
		$show_faq     = get_post_meta( $post->ID, '_sfaq_show_faq', true );
		$show_schema  = get_post_meta( $post->ID, '_sfaq_show_schema', true );
		$custom_title = get_post_meta( $post->ID, '_sfaq_custom_title', true );
		$global_title = SFAQ_Settings::get( 'global_title', 'Frequently Asked Questions' );

		$show_faq    = ( '' === $show_faq )    ? '1' : $show_faq;
		$show_schema = ( '' === $show_schema ) ? '1' : $show_schema;

		$shortcode = '[smart_faq id="' . $post->ID . '"]';
		?>
		<div id="sfaq-meta-box-wrapper" class="sfaq-admin-panel">

			<!-- PANEL HEADER -->
			<div class="sfaq-panel-header">
				<div class="sfaq-panel-header-left">
					<span class="sfaq-badge">FAQ</span>
					<span class="sfaq-panel-subtitle"><?php esc_html_e( 'Add FAQs below. They\'ll display on the frontend and generate schema markup.', 'smart-faq-schema' ); ?></span>
				</div>
				<div class="sfaq-panel-toggles">
					<label class="sfaq-toggle-label" title="<?php esc_attr_e( 'Show FAQ section to visitors on this post', 'smart-faq-schema' ); ?>">
						<input type="checkbox" name="sfaq_show_faq" value="1" <?php checked( $show_faq, '1' ); ?> class="sfaq-toggle-input" id="sfaq_show_faq">
						<span class="sfaq-toggle-switch"></span>
						<span class="sfaq-toggle-text"><?php esc_html_e( 'Show FAQs', 'smart-faq-schema' ); ?></span>
					</label>
					<label class="sfaq-toggle-label" title="<?php esc_attr_e( 'Generate FAQPage schema for this post', 'smart-faq-schema' ); ?>">
						<input type="checkbox" name="sfaq_show_schema" value="1" <?php checked( $show_schema, '1' ); ?> class="sfaq-toggle-input" id="sfaq_show_schema">
						<span class="sfaq-toggle-switch"></span>
						<span class="sfaq-toggle-text"><?php esc_html_e( 'Generate Schema', 'smart-faq-schema' ); ?></span>
					</label>
				</div>
			</div>

			<!-- CUSTOM SECTION TITLE -->
			<div class="sfaq-row sfaq-section-title-row">
				<label class="sfaq-label" for="sfaq_custom_title">
					<?php esc_html_e( 'Section Title', 'smart-faq-schema' ); ?>
					<?php
					/* translators: %s: the global section title set in plugin settings */
					$hint = sprintf( __( '(Leave blank to use global: "%s")', 'smart-faq-schema' ), $global_title );
					echo '<span class="sfaq-hint">' . esc_html( $hint ) . '</span>';
					?>
				</label>
				<input type="text"
					   id="sfaq_custom_title"
					   name="sfaq_custom_title"
					   value="<?php echo esc_attr( $custom_title ); ?>"
					   placeholder="<?php echo esc_attr( $global_title ); ?>"
					   class="sfaq-text-input">
			</div>

			<!-- FAQ LIST -->
			<div id="sfaq-faq-list" class="sfaq-faq-list">
				<?php if ( empty( $faqs ) ) : ?>
					<div class="sfaq-empty-state" id="sfaq-empty-state">
						<div class="sfaq-empty-icon">💬</div>
						<p><?php esc_html_e( 'No FAQs added yet. Click "Add FAQ" below to get started.', 'smart-faq-schema' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $faqs as $index => $faq ) : ?>
						<?php self::render_faq_row( $index, $faq ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- ADD FAQ BUTTON -->
			<div class="sfaq-actions-row">
				<button type="button" id="sfaq-add-faq" class="sfaq-btn sfaq-btn-primary">
					<span class="sfaq-btn-icon">+</span>
					<?php esc_html_e( 'Add FAQ', 'smart-faq-schema' ); ?>
				</button>
				<span class="sfaq-count-indicator">
					<span id="sfaq-count"><?php echo count( $faqs ); ?></span> <?php esc_html_e( 'FAQ(s)', 'smart-faq-schema' ); ?>
				</span>
			</div>

			<!-- SHORTCODE INFO -->
			<div class="sfaq-shortcode-row">
				<span class="sfaq-shortcode-label"><?php esc_html_e( 'Shortcode:', 'smart-faq-schema' ); ?></span>
				<code class="sfaq-shortcode-code" id="sfaq-shortcode-display"><?php echo esc_html( $shortcode ); ?></code>
				<button type="button" class="sfaq-btn-copy sfaq-copy-shortcode" data-code="<?php echo esc_attr( $shortcode ); ?>">
					📋 <?php esc_html_e( 'Copy', 'smart-faq-schema' ); ?>
				</button>
				<span class="sfaq-copy-feedback" style="display:none;">✔ <?php esc_html_e( 'Copied!', 'smart-faq-schema' ); ?></span>
			</div>

			<!-- JS TEMPLATE ROW (hidden) -->
			<script type="text/html" id="sfaq-row-template">
				<?php self::render_faq_row( '{{INDEX}}', array( 'question' => '', 'answer' => '' ), true ); ?>
			</script>

		</div>
		<?php
	}

	/**
	 * Render a single FAQ row.
	 */
	public static function render_faq_row( $index, $faq, $template = false ) {
		$q   = isset( $faq['question'] ) ? $faq['question'] : '';
		$a   = isset( $faq['answer'] )   ? $faq['answer']   : '';
		$idx = $template ? '{{INDEX}}' : intval( $index );
		?>
		<div class="sfaq-faq-row" data-index="<?php echo esc_attr( $idx ); ?>">
			<div class="sfaq-faq-row-header">
				<span class="sfaq-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'smart-faq-schema' ); ?>">⠿</span>
				<span class="sfaq-faq-number"><?php echo $template ? '{{NUM}}' : ( intval( $index ) + 1 ); ?></span>
				<button type="button" class="sfaq-btn-remove-faq" title="<?php esc_attr_e( 'Remove FAQ', 'smart-faq-schema' ); ?>">✕</button>
			</div>
			<div class="sfaq-faq-row-body">
				<div class="sfaq-field">
					<label class="sfaq-field-label"><?php esc_html_e( 'Question (H3)', 'smart-faq-schema' ); ?></label>
					<input type="text"
						   name="sfaq_faqs[<?php echo esc_attr( $idx ); ?>][question]"
						   value="<?php echo esc_attr( $q ); ?>"
						   placeholder="<?php esc_attr_e( 'Enter question here...', 'smart-faq-schema' ); ?>"
						   class="sfaq-input-question sfaq-full-input"
						   autocomplete="off">
				</div>
				<div class="sfaq-field">
					<label class="sfaq-field-label"><?php esc_html_e( 'Answer', 'smart-faq-schema' ); ?></label>
					<textarea name="sfaq_faqs[<?php echo esc_attr( $idx ); ?>][answer]"
							  placeholder="<?php esc_attr_e( 'Enter answer here...', 'smart-faq-schema' ); ?>"
							  class="sfaq-input-answer sfaq-full-input"
							  rows="3"><?php echo esc_textarea( $a ); ?></textarea>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data — Classic Editor.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Nonce verified here — suppress PHPCS false-positive on do_save()
		if ( ! isset( $_POST['sfaq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sfaq_nonce'] ) ), 'sfaq_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		// Nonce is verified above — safe to call do_save
		self::do_save( $post_id );
	}

	/**
	 * Save meta box data — Block Editor (REST).
	 */
	public static function save_from_rest( $post, $request ) {
		$post_id = $post->ID;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$params = $request->get_params();

		if ( isset( $params['sfaq_faqs'] ) ) {
			self::save_faqs_data( $post_id, $params['sfaq_faqs'] );
		}
		if ( isset( $params['sfaq_show_faq'] ) ) {
			update_post_meta( $post_id, '_sfaq_show_faq', $params['sfaq_show_faq'] ? '1' : '0' );
		}
		if ( isset( $params['sfaq_show_schema'] ) ) {
			update_post_meta( $post_id, '_sfaq_show_schema', $params['sfaq_show_schema'] ? '1' : '0' );
		}
		if ( isset( $params['sfaq_custom_title'] ) ) {
			update_post_meta( $post_id, '_sfaq_custom_title', sanitize_text_field( $params['sfaq_custom_title'] ) );
		}
	}

	/**
	 * Common save logic — nonce already verified by caller.
	 */
	private static function do_save( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in save() before this call.
		$show_faq = isset( $_POST['sfaq_show_faq'] ) ? '1' : '0';
		update_post_meta( $post_id, '_sfaq_show_faq', $show_faq );

		$show_schema = isset( $_POST['sfaq_show_schema'] ) ? '1' : '0';
		update_post_meta( $post_id, '_sfaq_show_schema', $show_schema );

		$custom_title = isset( $_POST['sfaq_custom_title'] )
			? sanitize_text_field( wp_unslash( $_POST['sfaq_custom_title'] ) )
			: '';
		update_post_meta( $post_id, '_sfaq_custom_title', $custom_title );

		$raw = isset( $_POST['sfaq_faqs'] ) ? wp_unslash( $_POST['sfaq_faqs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in save_faqs_data()
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		self::save_faqs_data( $post_id, $raw );
	}

	/**
	 * Sanitize and persist the FAQ array.
	 */
	private static function save_faqs_data( $post_id, $raw ) {
		$faqs = array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $item ) {
				$q = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
				$a = isset( $item['answer'] )   ? wp_kses_post( $item['answer'] )           : '';
				if ( '' !== $q || '' !== $a ) {
					$faqs[] = array(
						'question' => $q,
						'answer'   => $a,
					);
				}
			}
		}
		update_post_meta( $post_id, '_sfaq_faqs', $faqs );
		// Clear dashboard count cache so stats update immediately
		delete_transient( 'sfaq_posts_with_faq_count' );
		wp_cache_delete( 'sfaq_posts_with_faq_count', 'smart_faq_schema' );
	}
}
