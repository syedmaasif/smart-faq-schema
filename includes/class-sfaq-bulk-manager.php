<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Bulk_Manager {

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue( $hook ) {
		if ( false === strpos( $hook, 'sfaq' ) ) {
			return;
		}
		wp_enqueue_style(
			'sfaq-admin',
			SFAQ_PLUGIN_URL . 'admin/css/sfaq-admin.css',
			array(),
			SFAQ_VERSION
		);
	}

	/**
	 * Render the Bulk Manager page.
	 * GET params here are read-only UI state (filter/search/page).
	 * No data is written from these params — nonce not required per PHPCS recommendation for read-only display filters.
	 */
	public static function render_page() {
		// Verify user capability first
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smart-faq-schema' ) );
		}

		$post_types = apply_filters( 'sfaq_bulk_post_types', array( 'post', 'page' ) );
		if ( post_type_exists( 'product' ) ) {
			$post_types[] = 'product';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter/search params; no data written.
		$active_type   = isset( $_GET['sfaq_pt'] )     ? sanitize_key( wp_unslash( $_GET['sfaq_pt'] ) )              : 'post';
		$active_filter = isset( $_GET['sfaq_filter'] ) ? sanitize_key( wp_unslash( $_GET['sfaq_filter'] ) )          : 'all';
		$search        = isset( $_GET['sfaq_search'] ) ? sanitize_text_field( wp_unslash( $_GET['sfaq_search'] ) )   : '';
		$paged         = isset( $_GET['sfaq_paged'] )  ? max( 1, intval( $_GET['sfaq_paged'] ) )                      : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_page = 20;

		if ( ! in_array( $active_type, $post_types, true ) ) {
			$active_type = $post_types[0];
		}

		$args = array(
			'post_type'      => $active_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		if ( 'has_faq' === $active_filter ) {
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional: filtering posts by FAQ meta presence.
			$args['meta_query'] = array(
				array(
					'key'     => '_sfaq_faqs',
					'compare' => 'EXISTS',
				),
			);
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		} elseif ( 'no_faq' === $active_filter ) {
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query'] = array(
				array(
					'key'     => '_sfaq_faqs',
					'compare' => 'NOT EXISTS',
				),
			);
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query = new WP_Query( $args );
		$posts = $query->posts;
		$total = $query->found_posts;
		$pages = (int) ceil( $total / $per_page );
		?>
		<div class="sfaq-admin-page wrap">
			<div class="sfaq-page-header">
				<h1 class="sfaq-page-title">
					<span class="sfaq-page-icon">📋</span>
					<?php esc_html_e( 'FAQ Bulk Manager', 'smart-faq-schema' ); ?>
				</h1>
				<p class="sfaq-page-desc"><?php esc_html_e( 'View all posts/pages and see which ones have FAQs. Click any post to add or edit its FAQs.', 'smart-faq-schema' ); ?></p>
			</div>

			<!-- Post Type Tabs -->
			<div class="sfaq-tab-bar">
				<?php foreach ( $post_types as $pt ) :
					$pt_obj   = get_post_type_object( $pt );
					$pt_label = $pt_obj ? $pt_obj->labels->name : ucfirst( $pt );
					$tab_url  = add_query_arg( array( 'page' => 'sfaq-bulk-manager', 'sfaq_pt' => $pt ), admin_url( 'admin.php' ) );
					$active   = ( $pt === $active_type ) ? ' sfaq-tab-active' : '';
				?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="sfaq-tab<?php echo esc_attr( $active ); ?>">
						<?php echo esc_html( $pt_label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<!-- Filters + Search -->
			<div class="sfaq-filter-bar">
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="sfaq-search-form">
					<input type="hidden" name="page" value="sfaq-bulk-manager">
					<input type="hidden" name="sfaq_pt" value="<?php echo esc_attr( $active_type ); ?>">
					<div class="sfaq-filter-group">
						<select name="sfaq_filter" class="sfaq-select">
							<option value="all"     <?php selected( $active_filter, 'all' ); ?>><?php esc_html_e( 'All Posts', 'smart-faq-schema' ); ?></option>
							<option value="has_faq" <?php selected( $active_filter, 'has_faq' ); ?>><?php esc_html_e( 'Has FAQs', 'smart-faq-schema' ); ?></option>
							<option value="no_faq"  <?php selected( $active_filter, 'no_faq' ); ?>><?php esc_html_e( 'No FAQs', 'smart-faq-schema' ); ?></option>
						</select>
						<input type="text" name="sfaq_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search posts...', 'smart-faq-schema' ); ?>" class="sfaq-search-input">
						<button type="submit" class="sfaq-btn sfaq-btn-secondary"><?php esc_html_e( 'Filter', 'smart-faq-schema' ); ?></button>
						<?php if ( '' !== $search || 'all' !== $active_filter ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sfaq-bulk-manager', 'sfaq_pt' => $active_type ), admin_url( 'admin.php' ) ) ); ?>" class="sfaq-btn sfaq-btn-ghost"><?php esc_html_e( 'Clear', 'smart-faq-schema' ); ?></a>
						<?php endif; ?>
					</div>
				</form>
				<div class="sfaq-result-count">
					<?php
					/* translators: %d: number of posts found */
					printf( esc_html__( '%d posts found', 'smart-faq-schema' ), intval( $total ) );
					?>
				</div>
			</div>

			<!-- Posts Table -->
			<div class="sfaq-table-wrapper">
				<table class="sfaq-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'smart-faq-schema' ); ?></th>
							<th><?php esc_html_e( 'FAQ Status', 'smart-faq-schema' ); ?></th>
							<th><?php esc_html_e( 'FAQ Count', 'smart-faq-schema' ); ?></th>
							<th><?php esc_html_e( 'Show FAQs', 'smart-faq-schema' ); ?></th>
							<th><?php esc_html_e( 'Schema', 'smart-faq-schema' ); ?></th>
							<th><?php esc_html_e( 'Action', 'smart-faq-schema' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $posts ) ) : ?>
							<tr><td colspan="6" class="sfaq-table-empty"><?php esc_html_e( 'No posts found.', 'smart-faq-schema' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $posts as $p ) :
								$faqs        = get_post_meta( $p->ID, '_sfaq_faqs', true );
								$has_faqs    = ! empty( $faqs ) && is_array( $faqs );
								$faq_count   = $has_faqs ? count( $faqs ) : 0;
								$show_faq    = get_post_meta( $p->ID, '_sfaq_show_faq', true );
								$show_schema = get_post_meta( $p->ID, '_sfaq_show_schema', true );
								$edit_url    = get_edit_post_link( $p->ID );
							?>
							<tr class="sfaq-table-row <?php echo $has_faqs ? 'sfaq-row-has-faq' : 'sfaq-row-no-faq'; ?>">
								<td class="sfaq-col-title">
									<a href="<?php echo esc_url( $edit_url ); ?>" class="sfaq-post-link"><?php echo esc_html( $p->post_title ); ?></a>
									<span class="sfaq-post-type-badge"><?php echo esc_html( $p->post_type ); ?></span>
								</td>
								<td class="sfaq-col-status">
									<?php if ( $has_faqs ) : ?>
										<span class="sfaq-status-badge sfaq-status-has">&#10004; <?php esc_html_e( 'Has FAQs', 'smart-faq-schema' ); ?></span>
									<?php else : ?>
										<span class="sfaq-status-badge sfaq-status-none">&#10007; <?php esc_html_e( 'No FAQs', 'smart-faq-schema' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="sfaq-col-count">
									<?php echo $has_faqs ? '<span class="sfaq-count-pill">' . intval( $faq_count ) . '</span>' : '&mdash;'; ?>
								</td>
								<td class="sfaq-col-toggle">
									<?php if ( '0' === $show_faq ) : ?>
										<span class="sfaq-mini-badge sfaq-badge-off"><?php esc_html_e( 'Hidden', 'smart-faq-schema' ); ?></span>
									<?php else : ?>
										<span class="sfaq-mini-badge sfaq-badge-on"><?php esc_html_e( 'Visible', 'smart-faq-schema' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="sfaq-col-schema">
									<?php if ( '0' === $show_schema ) : ?>
										<span class="sfaq-mini-badge sfaq-badge-off"><?php esc_html_e( 'Off', 'smart-faq-schema' ); ?></span>
									<?php else : ?>
										<span class="sfaq-mini-badge sfaq-badge-on"><?php esc_html_e( 'On', 'smart-faq-schema' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="sfaq-col-action">
									<a href="<?php echo esc_url( $edit_url . '#sfaq-meta-box-wrapper' ); ?>" class="sfaq-btn sfaq-btn-edit sfaq-btn-sm">
										<?php echo $has_faqs ? esc_html__( 'Edit FAQs', 'smart-faq-schema' ) : esc_html__( 'Add FAQs', 'smart-faq-schema' ); ?>
									</a>
									<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" target="_blank" class="sfaq-btn sfaq-btn-ghost sfaq-btn-sm">&#8599;</a>
								</td>
							</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Pagination -->
			<?php if ( $pages > 1 ) : ?>
			<div class="sfaq-pagination">
				<?php for ( $i = 1; $i <= $pages; $i++ ) :
					$page_url = add_query_arg( array(
						'page'        => 'sfaq-bulk-manager',
						'sfaq_pt'     => $active_type,
						'sfaq_paged'  => $i,
						'sfaq_filter' => $active_filter,
					), admin_url( 'admin.php' ) );
				?>
					<a href="<?php echo esc_url( $page_url ); ?>" class="sfaq-page-num <?php echo ( $i === $paged ) ? 'sfaq-page-active' : ''; ?>"><?php echo intval( $i ); ?></a>
				<?php endfor; ?>
			</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
