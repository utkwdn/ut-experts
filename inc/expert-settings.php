<?php
/**
 * Expert Settings.
 *
 * Adds an "Expert Settings" page under the Experts menu for the plugin's global
 * settings: the media contact email/phone shown on expert profiles, and the
 * Expert Search page that area-of-expertise links point to.
 *
 * @package UtkwdsExperts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Set up cap to allow non-admins to edit settings on this page.
 *
 */
if ( ! defined( 'UT_EXPERTS_SETTINGS_CAP' ) ) {
	define( 'UT_EXPERTS_SETTINGS_CAP', 'edit_posts' );
}

/**
 * Build a URL to the filtered Expert Search page.
 * Fall back to linking to archive page if Expert Search page hasn't been set.
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

	$url = add_query_arg( $args, $base ) . '#filters';

	return apply_filters( 'ut_experts_area_search_url', $url, $term, $page_id );
}

/**
 * Register the "Expert Settings" admin page as a submenu of the Experts CPT.
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=expert',
			__( 'Expert Settings', 'ut-experts' ),
			__( 'Expert Settings', 'ut-experts' ),
			UT_EXPERTS_SETTINGS_CAP,
			'ut-experts-settings',
			'ut_experts_settings_page'
		);
	}
);

/**
 * Register the plugin's global settings under one option group.
 */
add_action(
	'admin_init',
	function () {
		register_setting(
			'ut_experts_settings',
			'ut_experts_search_page',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
		register_setting(
			'ut_experts_settings',
			'ut_experts_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);
		register_setting(
			'ut_experts_settings',
			'ut_experts_phone',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}
);

/**
 * Allow the configured (non-admin) capability to save these settings.
 */
add_filter(
	'option_page_capability_ut_experts_settings',
	function () {
		return UT_EXPERTS_SETTINGS_CAP;
	}
);

/**
 * Render the "Expert Settings" page.
 *
 * Values are stored as site options and used on expert profile pages and by
 * the expert-profile block's area links.
 *
 * @return void
 */
function ut_experts_settings_page() {
	if ( ! current_user_can( UT_EXPERTS_SETTINGS_CAP ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'ut-experts' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Expert Settings', 'ut-experts' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'ut_experts_settings' ); ?>
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
								'selected'          => (int) get_option( 'ut_experts_search_page', 0 ),
								'show_option_none'  => esc_html__( '— Select a page —', 'ut-experts' ),
								'option_none_value' => '0',
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ut_experts_email"><?php esc_html_e( 'Media contact email', 'ut-experts' ); ?></label>
					</th>
					<td>
						<input type="email" id="ut_experts_email" name="ut_experts_email" value="<?php echo esc_attr( get_option( 'ut_experts_email', '' ) ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ut_experts_phone"><?php esc_html_e( 'Media contact phone', 'ut-experts' ); ?></label>
					</th>
					<td>
						<input type="text" id="ut_experts_phone" name="ut_experts_phone" value="<?php echo esc_attr( get_option( 'ut_experts_phone', '' ) ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
