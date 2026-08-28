<?php
/**
 * Media Contact.
 *
 * Adds a "Media Contact" page under the Experts menu to allow
 * globally setting email and phone.
 *
 * @package utExperts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the "Media Contact" admin page as a submenu of the Experts CPT.
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=expert',
			__( 'Media Contact', 'ut-experts' ),
			__( 'Media Contact', 'ut-experts' ),
			'manage_options',
			'ut-experts-contact',
			'ut_experts_contact_page'
		);
	}
);

/**
 * Register settings for phone and email.
 */
add_action(
	'admin_init',
	function () {
		register_setting(
			'ut_experts_contact',
			'ut_experts_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);
		register_setting(
			'ut_experts_contact',
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
 * Render the "Media Contact" settings page.
 *
 * Display Media Contact email and phone form.
 * Values are stored as site options and used on expert profile pages.
 *
 * @return void
 */
function ut_experts_contact_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Media Contact', 'ut-experts' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'ut_experts_contact' ); ?>
			<div style="margin-top:20px;">
				<label for="ut_experts_email" style="min-width: 50px;display: inline-block;"><?php esc_html_e( 'Email', 'ut-experts' ); ?></label>
				<input type="email" id="ut_experts_email" name="ut_experts_email" value="<?php echo esc_attr( get_option( 'ut_experts_email', '' ) ); ?>" class="regular-text" />
			</div>
			<div style="margin-top:10px;">
				<label for="ut_experts_phone" style="min-width: 50px;display: inline-block;"><?php esc_html_e( 'Phone', 'ut-experts' ); ?></label>
				<input type="text" id="ut_experts_phone" name="ut_experts_phone" value="<?php echo esc_attr( get_option( 'ut_experts_phone', '' ) ); ?>" class="regular-text" />
			</div>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}