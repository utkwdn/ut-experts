<?php
/**
 * Experts settings.
 *
 * Registers the "Experts search page" option and renders a settings section on
 * the Manage Experts admin page. The expert-profile block uses this option to
 * point area-of-expertise links at a pre-filtered view of the search page,
 * rather than the term archive.
 *
 * @package UtkwdsExperts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Build a URL to the configured Expert Search page, pre-filtered to an area term.
 *
 * Returns '' when no search page has been configured, so callers can fall back
 * to the term archive. Top-level areas map to `?area=<id>`; child terms map to
 * `?area=<top ancestor>&subarea=<id>` so the search block's selects and chips
 * populate correctly.
 *
 * @param WP_Term $term Area of expertise term.
 * @return string Filtered search URL, or '' if unconfigured.
 */
function utkwds_expert_area_search_url( $term ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		return '';
	}

	$page_id = (int) get_option( 'ut_experts_search_page', 0 );
	if ( ! $page_id ) {
		return '';
	}

	$base = get_permalink( $page_id );
	if ( ! $base ) {
		return '';
	}

	$taxonomy = 'ut_expert_area_of_expertise';

	if ( 0 === (int) $term->parent ) {
		$args = array( 'area' => (int) $term->term_id );
	} else {
		$ancestors = get_ancestors( (int) $term->term_id, $taxonomy, 'taxonomy' );
		$top       = ! empty( $ancestors ) ? (int) end( $ancestors ) : (int) $term->parent;
		$args      = array(
			'area'    => $top,
			'subarea' => (int) $term->term_id,
		);
	}

	$url = add_query_arg( $args, $base );

	/**
	 * Filter the area-of-expertise search URL used on expert profiles.
	 *
	 * @param string  $url     The generated URL.
	 * @param WP_Term $term    The area term being linked.
	 * @param int     $page_id The configured search page ID.
	 */
	return apply_filters( 'ut_experts_area_search_url', $url, $term, $page_id );
}

/**
 * Handle the settings form submission.
 *
 * Manual nonce handling to match the import/export forms on the same page.
 *
 * @return bool True when a save was processed.
 */
function ut_experts_handle_settings_save() {
	if ( ! isset( $_POST['ut_experts_settings_submit'] ) ) {
		return false;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( ! isset( $_POST['ut_experts_settings_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ut_experts_settings_nonce'] ) ), 'ut_experts_settings' )
	) {
		return false;
	}

	$page_id = isset( $_POST['ut_experts_search_page'] ) ? absint( wp_unslash( $_POST['ut_experts_search_page'] ) ) : 0;
	update_option( 'ut_experts_search_page', $page_id );

	return true;
}

/**
 * Render the settings section on the Manage Experts page.
 */
function ut_experts_render_settings_section() {
	$saved = ut_experts_handle_settings_save();

	if ( $saved ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Settings saved.', 'ut-experts' )
		);
	}

	$selected = (int) get_option( 'ut_experts_search_page', 0 );
	?>
	<div class="expert-section" style="margin: 30px 0; padding: 0 0 20px; border-bottom: 1px solid #d4d4d4;">
		<h2><?php esc_html_e( 'Settings', 'ut-experts' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'ut_experts_settings', 'ut_experts_settings_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="ut_experts_search_page"><?php esc_html_e( 'Experts search page', 'ut-experts' ); ?></label>
					</th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'ut_experts_search_page',
								'id'                => 'ut_experts_search_page',
								'selected'          => $selected,
								'show_option_none'  => esc_html__( '— Select a page —', 'ut-experts' ),
								'option_none_value' => '0',
							)
						);
						?>
						<p class="description">
							<?php esc_html_e( 'The page that contains the Expert Search block. Area-of-expertise links on expert profiles point to this page, pre-filtered to the selected area.', 'ut-experts' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Settings', 'ut-experts' ), 'primary', 'ut_experts_settings_submit' ); ?>
		</form>
	</div>
	<?php
}
