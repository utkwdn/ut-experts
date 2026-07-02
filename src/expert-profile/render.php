<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package UtkwdsExperts
 */

// Check block's post context ID first, fall back to the current post in the loop.
$expert_post_id = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();

if ( ! $expert_post_id ) {
	return;
}


if ( ! function_exists( 'utkwds_expert_field' ) ) {
	/**
	 * Read an ACF field with a fallback to meta if ACF is inactive.
	 *
	 * @param string     $selector ACF field name.
	 * @param int|string $target   Post ID or ACF term selector (e.g. "taxonomy_123").
	 * @return string
	 */
	function utkwds_expert_field( $selector, $target ) {
		if ( function_exists( 'get_field' ) ) {
			return (string) get_field( $selector, $target );
		}
		// Core fallbacks when ACF isn't loaded.
		if ( is_numeric( $target ) ) {
			return (string) get_post_meta( (int) $target, $selector, true );
		}
		return '';
	}
}


if ( ! function_exists( 'utkwds_expert_term_links' ) ) {
	/**
	 * Get colleges, departments and centers along with websites if entered.
	 *
	 * @param int    $expert_post_id   Expert post ID.
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $url_field ACF field name storing the term's website URL.
	 * @return array<int, array{name:string, url:string}>
	 */
	function utkwds_expert_term_links( $expert_post_id, $taxonomy, $url_field ) {
		$terms = get_the_terms( $expert_post_id, $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$links = array();
		foreach ( $terms as $term ) {
			$url = '';
			if ( function_exists( 'get_field' ) ) {
				$url = (string) get_field( $url_field, $taxonomy . '_' . $term->term_id );
			} else {
				$url = (string) get_term_meta( $term->term_id, $url_field, true );
			}
			$links[] = array(
				'name' => $term->name,
				'url'  => $url,
			);
		}
		return $links;
	}
}

// Core fields.
$name         = get_the_title( $expert_post_id );
$expert_title = utkwds_expert_field( 'expert_title', $expert_post_id );
$website      = utkwds_expert_field( 'expert_website', $expert_post_id );
$bio          = get_post_field( 'post_content', $expert_post_id );
$placeholder  = get_theme_file_uri( 'assets/images/person-placeholder.jpeg' );

// First name for the profile link label.
$first_name = 'expert';
if ( '' !== trim( (string) $name ) ) {
	$name_parts = preg_split( '/\s+/', trim( $name ) );
	$first_name = $name_parts[0];
}

// Areas of expertise terms.
$areas = get_the_terms( $expert_post_id, 'ut_expert_area_of_expertise' );
if ( empty( $areas ) || is_wp_error( $areas ) ) {
	$areas = array();
}

// Combine colleges, depapartments and centers.
$additional_links = array_merge(
	utkwds_expert_term_links( $expert_post_id, 'ut_expert_college', 'expert_college_url' ),
	utkwds_expert_term_links( $expert_post_id, 'ut_expert_department', 'expert_department_url' ),
	utkwds_expert_term_links( $expert_post_id, 'ut_expert_center', 'expert_center_url' )
);

// To Do: Wire up to customizer options or other setting.
$email = 'email@utk.edu';
$phone = '(865) 974-1001';
?>

<div class="wp-block-group has-global-padding is-layout-constrained">
	<h1 class="wp-block-post-title alignwide"><?php echo esc_html( $name ); ?></h1>
</div>

<div class="entry-content wp-block-post-content has-global-padding is-layout-constrained" style="margin-block-start: 0;">
	<?php if ( '' !== $expert_title ) : ?>
	<p class="is-style-utkwds-paragraph-large wp-block-paragraph alignwide"><?php echo esc_html( $expert_title ); ?></p>
	<?php endif; ?>

	<div class="wp-block-columns is-style-columns-reverse is-layout-flex alignwide">
		<div class="wp-block-column is-layout-flow" style="flex-basis: 30%" >
			<div class="wp-block-group utkwds-contact-single utkwds-contact-single--large has-light-background-color has-background is-content-justification-left is-nowrap is-layout-flex" >
				<figure class="wp-block-image">
					<?php if ( has_post_thumbnail( $expert_post_id ) ) : ?>
						<?php
						echo get_the_post_thumbnail(
							$expert_post_id,
							'large',
							array(
								'decoding' => 'async',
								'alt'      => esc_attr( $name ),
							)
						);
						?>
					<?php else : ?>
					<img decoding="async" src="<?php echo esc_url( $placeholder ); ?>" alt="<?php echo esc_attr( $name ? $name : 'person placeholder' ); ?>" />
					<?php endif; ?>
				</figure>
			</div>
		</div>

		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis: 70%" >
			<?php if ( '' !== trim( (string) $bio ) ) : ?>
				<?php echo wp_kses_post( wpautop( $bio ) ); ?>
			<?php endif; ?>

			<?php if ( '' !== $website ) : ?>
			<p class="wp-block-paragraph" style="margin-block-start: 10px;">
				<a href="<?php echo esc_url( $website ); ?>">
					<?php
					if ( '' !== $first_name ) {
						/* translators: %s: expert's first name */
						printf( esc_html__( "View %s's full profile", 'ut-experts' ), esc_html( $first_name ) );
					} else {
						esc_html_e( 'View full profile', 'ut-experts' );
					}
					?>
				</a>
			</p>
			<?php endif; ?>

			<?php if ( ! empty( $areas ) ) : ?>
			<p class="wp-block-paragraph"><strong><?php esc_html_e( 'Area of expertise', 'ut-experts' ); ?></strong></p>

			<div class="taxonomy-category wp-block-post-terms" style="margin-block-start: 5px;">
				<?php
				foreach ( $areas as $area ) :
					$area_link = get_term_link( $area );
					$area_href = is_wp_error( $area_link ) ? '#' : $area_link;
					?>
				<a href="<?php echo esc_url( $area_href ); ?>" rel="tag"><?php echo esc_html( $area->name ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $additional_links ) ) : ?>
			<p class="wp-block-paragraph"><strong><?php esc_html_e( 'Additional links', 'ut-experts' ); ?></strong></p>

			<p class="wp-block-paragraph" style="margin-block-start: 5px;">
				<?php
				$rendered = array();
				foreach ( $additional_links as $additional_link ) {
					if ( '' !== $additional_link['url'] ) {
						$rendered[] = '<a href="' . esc_url( $additional_link['url'] ) . '">' . esc_html( $additional_link['name'] ) . '</a>';
					} else {
						$rendered[] = esc_html( $additional_link['name'] );
					}
				}
				echo wp_kses_post( implode( '<br />', $rendered ) );
				?>
			</p>
			<?php endif; ?>

			<hr class="wp-block-separator has-text-color has-gray-2-color has-alpha-channel-opacity has-gray-2-background-color has-background" style="border:none;" />

			<?php if ( '' !== $email || '' !== $phone ) : ?>
			<div class="wp-block-group alignfull utkwds-icon-text is-layout-constrained">
				<div class="wp-block-columns is-layout-flex" style="gap:20px;margin: 0 !important;">
					<div>
						<div class="wp-block-utk-wds-icon-block items-justified-center">
							<div class="icon-container" style="width: 50px; margin:0;">
								<svg viewBox="0 0 126 125" fill="none" xmlns="http://www.w3.org/2000/svg" >
									<path d="M61.11 30.51c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm0 0c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm-4.55 13.02V97.9h3.67V43.53h-3.67Zm4.55-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm7.04 13.02V97.9h.32V43.53h-.32Zm0 0V97.9h.32V43.53h-.32Zm-11.59 0V97.9h3.67V43.53h-3.67Zm4.55-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm-4.55 13.02V97.9h3.67V43.53h-3.67Zm4.55-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm7.04 13.02V97.9h.32V43.53h-.32Zm0 0V97.9h.32V43.53h-.32ZM56.56 97.9h3.67V43.53h-3.67V97.9Zm4.55-67.39c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm7.04 13.02V97.9h.32V43.53h-.32Zm-7.04-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84ZM56.56 97.9h3.67V43.53h-3.67V97.9Zm4.55-67.39c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm-4.55 13.02V97.9h3.67V43.53h-3.67Zm11.59 0V97.9h.32V43.53h-.32Zm0 0V97.9h.32V43.53h-.32Zm-7.04-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm-4.55 13.02V97.9h3.67V43.53h-3.67Zm4.55-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm-4.55 13.02V97.9h3.67V43.53h-3.67Zm11.59 0V97.9h.32V43.53h-.32Zm0 0V97.9h.32V43.53h-.32Zm-7.04-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm-4.55 13.02V97.9h3.67V43.53h-3.67Zm4.55-13.02c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Zm0 0c0-2.35 1.65-4.33 3.86-4.83-.76-.39-1.59-.59-2.45-.59-2.99 0-5.43 2.44-5.43 5.45a5.441 5.441 0 0 0 7.96 4.81 4.945 4.945 0 0 1-3.94-4.84Z" fill="#FF8200" ></path>
									<path d="M62.5 0C28.02 0 0 28.02 0 62.48c0 34.48 28.02 62.5 62.5 62.5 34.46 0 62.5-28.02 62.5-62.5C125 28.02 96.96 0 62.5 0Zm0 122.52h-.69c-32.8-.36-59.35-27.14-59.35-60.04 0-32.97 26.71-59.82 59.65-60.04h.39c33.1 0 60.04 26.94 60.04 60.04 0 33.12-26.94 60.04-60.04 60.04Z" fill="#4B4B4B" ></path>
									<path d="M62.5 2.44h-.39C30.75 4.47 5.86 30.63 5.86 62.48c0 31.78 24.73 57.87 55.95 60.04h.69c33.1 0 60.04-26.92 60.04-60.04 0-33.1-26.94-60.04-60.04-60.04Zm.02 21.15c1.44 0 2.83.45 4.02 1.29.01.01.02.01.03.02.02.02.04.03.06.05.47.34.88.73 1.23 1.16.2.24.38.49.53.75a6.863 6.863 0 0 1 1.06 3.68 6.9 6.9 0 0 1-6.93 6.93c-3.82 0-6.93-3.11-6.93-6.93 0-3.83 3.11-6.95 6.93-6.95Zm7.45 75.06c0 .41-.34.75-.75.75H55.81c-.41 0-.75-.34-.75-.75V42.78c0-.41.34-.75.75-.75h13.41c.41 0 .75.34.75.75v55.87Z" fill="#FF8200" ></path>
									<path d="M68.39 26.86c-.15-.26-.33-.51-.53-.75-.35-.43-.76-.82-1.23-1.16-.02-.02-.04-.03-.06-.05-.01-.01-.02-.01-.03-.02a6.945 6.945 0 0 0-4.02-1.29c-3.82 0-6.93 3.12-6.93 6.95 0 3.82 3.11 6.93 6.93 6.93a6.9 6.9 0 0 0 6.93-6.93c0-1.32-.37-2.59-1.06-3.68Zm-2.53 7.98c-.26.2-.53.37-.81.51a5.441 5.441 0 0 1-7.96-4.81c0-3.01 2.44-5.45 5.43-5.45.86 0 1.69.2 2.45.59.24.12.47.25.69.41l.06.06c1.42 1.02 2.23 2.62 2.23 4.39 0 1.69-.76 3.26-2.09 4.3ZM60.23 97.9v.06h7.92v-.06h-7.92Z" fill="#4B4B4B" ></path>
									<path d="M69.22 42.03H55.81c-.41 0-.75.34-.75.75v55.87c0 .41.34.75.75.75h13.41c.41 0 .75-.34.75-.75V42.78c0-.41-.34-.75-.75-.75Zm-.75 55.87h-.32v.06h-7.92v-.06h-3.67V43.53h11.91V97.9Z" fill="#4B4B4B" ></path>
								</svg>
							</div>
						</div>
					</div>

					<div>
						<p class="wp-block-paragraph" style="margin: 0;"><strong><?php esc_html_e( 'Media contact information', 'ut-experts' ); ?></strong></p>

						<p class="wp-block-paragraph" style="margin-block-start: 5px;">
							<?php if ( '' !== $email ) : ?>
							<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
							<?php endif; ?>
							<?php
							if ( '' !== $email && '' !== $phone ) {
								echo ' &bull; ';
							}
							?>
							<?php if ( '' !== $phone ) : ?>
							<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
							<?php endif; ?>
						</p>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>