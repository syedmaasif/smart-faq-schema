<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Frontend {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer',          array( __CLASS__, 'auto_append_footer' ), 5 );
	}

	/**
	 * Enqueue frontend assets on any singular post that has FAQs and show_faq ON.
	 */
	public static function enqueue() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET params for Elementor detection; no data is written.
		if ( isset( $_GET['elementor-preview'] ) ) {
			return;
		}
		if ( isset( $_GET['action'] ) && 'elementor' === sanitize_key( $_GET['action'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$faqs = get_post_meta( $post_id, '_sfaq_faqs', true );
		if ( empty( $faqs ) || ! is_array( $faqs ) ) {
			return;
		}

		$show_faq = get_post_meta( $post_id, '_sfaq_show_faq', true );
		if ( '0' === $show_faq ) {
			return;
		}

		self::enqueue_assets();
	}

	/**
	 * Enqueue CSS and JS. Can be called by shortcode too.
	 */
	public static function enqueue_assets() {
		if ( ! wp_style_is( 'sfaq-frontend', 'enqueued' ) ) {
			wp_enqueue_style(
				'sfaq-frontend',
				SFAQ_PLUGIN_URL . 'frontend/css/sfaq-frontend.css',
				array(),
				SFAQ_VERSION
			);
			wp_add_inline_style( 'sfaq-frontend', SFAQ_Settings::get_css_variables() );
		}

		if ( ! wp_script_is( 'sfaq-frontend', 'enqueued' ) ) {
			wp_enqueue_script(
				'sfaq-frontend',
				SFAQ_PLUGIN_URL . 'frontend/js/sfaq-frontend.js',
				array(),
				SFAQ_VERSION,
				true
			);
		}
	}

	/**
	 * Auto-append via wp_footer + JS DOM injection.
	 *
	 * We use wp_footer + JS instead of the_content filter because:
	 * - Elementor builds its own content pipeline and bypasses the_content filter
	 * - wp_footer fires reliably after all content is rendered
	 * - JS finds the post content container and injects the FAQ after it
	 *
	 * Only fires when display_method = 'auto'.
	 */
	public static function auto_append_footer() {
		$method = SFAQ_Settings::get( 'display_method', 'shortcode' );
		if ( 'auto' !== $method ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$faq_html = self::render_faq_html( $post_id );
		if ( '' === $faq_html ) {
			return;
		}

		self::enqueue_assets();
		?>
		<div id="sfaq-auto-block" style="display:none"><?php echo wp_kses_post( $faq_html ); ?></div>
		<script>
		(function() {
			function sfaqAutoInject() {
				var block = document.getElementById('sfaq-auto-block');
				if ( ! block ) return;

				var selectors = [
					'.entry-content',
					'.post-content',
					'.the-content',
					'[itemprop="articleBody"]',
					'.elementor-widget-theme-post-content .elementor-widget-container',
					'.elementor-widget-post-content .elementor-widget-container',
					'article .elementor-widget-container',
					'article',
					'.site-main',
					'main'
				];

				var target = null;
				for ( var i = 0; i < selectors.length; i++ ) {
					target = document.querySelector( selectors[i] );
					if ( target ) break;
				}

				if ( target ) {
					block.removeAttribute('style');
					target.parentNode.insertBefore( block, target.nextSibling );
				} else {
					block.removeAttribute('style');
				}

				// Re-init accordion if JS already ran
				if ( typeof window.sfaqInitAll === 'function' ) {
					window.sfaqInitAll();
				}
			}

			if ( document.readyState === 'loading' ) {
				document.addEventListener( 'DOMContentLoaded', sfaqAutoInject );
			} else {
				sfaqAutoInject();
			}
		})();
		</script>
		<?php
	}

	/**
	 * Build the FAQ section HTML for a given post.
	 * Returns empty string when conditions are not met.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public static function render_faq_html( $post_id ) {
		$faqs = get_post_meta( $post_id, '_sfaq_faqs', true );
		if ( empty( $faqs ) || ! is_array( $faqs ) ) {
			return '';
		}

		$valid_faqs = array();
		foreach ( $faqs as $faq ) {
			$q = trim( isset( $faq['question'] ) ? $faq['question'] : '' );
			$a = trim( isset( $faq['answer'] )   ? $faq['answer']   : '' );
			if ( '' !== $q ) {
				$valid_faqs[] = array( 'question' => $q, 'answer' => $a );
			}
		}

		if ( empty( $valid_faqs ) ) {
			return '';
		}

		$show_faq = get_post_meta( $post_id, '_sfaq_show_faq', true );
		if ( '0' === $show_faq ) {
			return '';
		}

		$style         = SFAQ_Settings::get( 'ui_style', 'style1' );
		$global_title  = SFAQ_Settings::get( 'global_title', 'Frequently Asked Questions' );
		$custom_title  = get_post_meta( $post_id, '_sfaq_custom_title', true );
		$section_title = ( '' !== $custom_title ) ? $custom_title : $global_title;
		$show_title    = SFAQ_Settings::get( 'show_section_title', '1' );

		ob_start();
		?>
		<div class="sfaq-section sfaq-<?php echo esc_attr( $style ); ?>"
			 id="sfaq-section-<?php echo intval( $post_id ); ?>"
			 role="region"
			 aria-label="<?php echo esc_attr( $section_title ); ?>">

			<?php if ( '1' === $show_title && '' !== $section_title ) : ?>
				<h2 class="sfaq-section-title"><?php echo esc_html( $section_title ); ?></h2>
			<?php endif; ?>

			<div class="sfaq-accordion" id="sfaq-accordion-<?php echo intval( $post_id ); ?>">
				<?php foreach ( $valid_faqs as $i => $faq ) :
					$faq_id   = 'sfaq-item-' . intval( $post_id ) . '-' . intval( $i );
					$panel_id = $faq_id . '-panel';
				?>
				<div class="sfaq-item" id="<?php echo esc_attr( $faq_id ); ?>">
					<button class="sfaq-question"
							type="button"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							id="<?php echo esc_attr( $faq_id ); ?>-btn">
						<span class="sfaq-icon-wrap" aria-hidden="true">
							<span class="sfaq-icon-plus">+</span>
							<span class="sfaq-icon-minus" style="display:none">&#8722;</span>
						</span>
						<h3 class="sfaq-question-text"><?php echo esc_html( $faq['question'] ); ?></h3>
					</button>
					<div class="sfaq-answer"
						 id="<?php echo esc_attr( $panel_id ); ?>"
						 role="region"
						 aria-labelledby="<?php echo esc_attr( $faq_id ); ?>-btn"
						 hidden>
						<div class="sfaq-answer-inner">
							<?php echo wp_kses_post( wpautop( $faq['answer'] ) ); ?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}
}
