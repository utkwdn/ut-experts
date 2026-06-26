<?php
/**
 * Manage Experts.
 *
 * Adds a "Manage Experts" page under the Experts menu and handles
 * importing/updating Expert posts from an uploaded CSV.
 *
 * @package UtkwdsExperts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the "Manage Experts" admin page as a submenu of the Experts CPT.
 */
function ut_experts_register_admin_pages() {
	add_submenu_page(
		'edit.php?post_type=expert',
		__( 'Manage Experts', 'ut-experts' ),
		__( 'Manage Experts', 'ut-experts' ),
		'manage_options',
		'ut-experts-manage',
		'ut_experts_manage_page_render'
	);
}
add_action( 'admin_menu', 'ut_experts_register_admin_pages' );

/**
 * Render the Manage Experts page.
 */
function ut_experts_manage_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'ut-experts' ) );
	}

	$results = ut_experts_handle_import();

	$base_url = admin_url( 'edit.php?post_type=expert&page=ut-experts-manage' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Manage Experts', 'ut-experts' ); ?></h1>


		<?php
		if ( is_wp_error( $results ) ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $results->get_error_message() )
			);
		} elseif ( is_array( $results ) ) {
			ut_experts_render_results_notice( $results );
		}
		?>
		<div class="expert-section" style="margin: 30px 0; padding: 20px 0; border-top: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;">
			<h2>Import Experts from CSV</h2>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'ut_experts_import', 'ut_experts_import_nonce' ); ?>
				<input type="file" name="ut_experts_csv" id="ut_experts_csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Import Experts', 'ut-experts' ), 'primary', 'ut_experts_import_submit' ); ?>
			</form>
		</div>
	</div>
	<?php
}


/**
 * Display a summary notice of import results.
 *
 * @param array $results Result counts and row errors.
 */
function ut_experts_render_results_notice( $results ) {
	$class = empty( $results['errors'] ) ? 'notice-success' : 'notice-warning';
	printf(
		'<div class="notice %1$s"><p>%2$s</p>',
		esc_attr( $class ),
		sprintf(
			/* translators: 1: created count, 2: updated count, 3: skipped count */
			esc_html__( 'Import complete. Created: %1$d. Updated: %2$d. Skipped: %3$d.', 'ut-experts' ),
			(int) $results['created'],
			(int) $results['updated'],
			(int) $results['skipped']
		)
	);

	if ( ! empty( $results['errors'] ) ) {
		echo '<ul style="list-style:disc;margin-left:20px;">';
		foreach ( $results['errors'] as $error ) {
			printf( '<li>%s</li>', esc_html( $error ) );
		}
		echo '</ul>';
	}
	echo '</div>';
}

/**
 * Validate upload and process the CSV.
 *
 * @return array|WP_Error|null Result counts on success, WP_Error on a fatal problem, or null when no import was submitted.
 */
function ut_experts_handle_import() {

	if ( ! isset( $_POST['ut_experts_import_submit'] ) ) {
		return null;
	}

	// Verify the nonce before processing form data.
	if ( ! isset( $_POST['ut_experts_import_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ut_experts_import_nonce'] ) ), 'ut_experts_import' )
	) {
		return new WP_Error( 'bad_nonce', __( 'Security check failed. Please reload the page and try again.', 'ut-experts' ) );
	}

	if ( empty( $_FILES['ut_experts_csv']['name'] ) ) {
		return new WP_Error( 'no_file', __( 'No file was uploaded.', 'ut-experts' ) );
	}

	$file = $_FILES['ut_experts_csv']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
		return new WP_Error( 'upload_error', __( 'The file failed to upload. Please try again.', 'ut-experts' ) );
	}

	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'not_uploaded', __( 'Invalid upload.', 'ut-experts' ) );
	}

	$check = wp_check_filetype( sanitize_file_name( $file['name'] ), array( 'csv' => 'text/csv' ) );
	if ( 'csv' !== $check['ext'] ) {
		return new WP_Error( 'bad_type', __( 'Please upload a .csv file.', 'ut-experts' ) );
	}

	$handle = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $handle ) {
		return new WP_Error( 'open_failed', __( 'Could not read the uploaded file.', 'ut-experts' ) );
	}

	// Read and normalize the header row.
	$header = fgetcsv( $handle );
	if ( false === $header ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error( 'empty_file', __( 'The CSV appears to be empty.', 'ut-experts' ) );
	}
	$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );

	// Map column name => index.
	$columns = array();
	foreach ( $header as $index => $name ) {
		$columns[ trim( strtolower( $name ) ) ] = $index;
	}

	if ( ! isset( $columns['title'] ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error( 'no_title_col', __( 'The CSV must include a "title" column.', 'ut-experts' ) );
	}

	$results = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'errors'  => array(),
	);

	// Deduped term IDs whose URL meta have already been written.
	$urls_written = array();
	$line         = 1; // Header was line 1.

	// Large files can take a while.
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}

	while ( false !== ( $raw = fgetcsv( $handle ) ) ) {
		++$line;

		// Skip empty lines.
		if ( null === $raw || ( 1 === count( $raw ) && ( null === $raw[0] || '' === trim( (string) $raw[0] ) ) ) ) {
			continue;
		}

		$row     = ut_experts_map_row( $raw, $columns );
		$outcome = ut_experts_import_row( $row, $urls_written );

		if ( is_wp_error( $outcome ) ) {
			++$results['skipped'];
			$results['errors'][] = sprintf(
				/* translators: 1: line number, 2: error message */
				__( 'Row %1$d skipped: %2$s', 'ut-experts' ),
				$line,
				$outcome->get_error_message()
			);
			continue;
		}

		++$results[ $outcome ]; // 'created' or 'updated'.
	}

	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	return $results;
}

/**
 * Turn csv row into a name => value map using the header column index.
 *
 * @param array $raw     Raw fgetcsv values.
 * @param array $columns Column name => index map.
 * @return array Column name => string value.
 */
function ut_experts_map_row( $raw, $columns ) {
	$row = array();
	foreach ( $columns as $name => $index ) {
		$row[ $name ] = isset( $raw[ $index ] ) ? trim( (string) $raw[ $index ] ) : '';
	}
	return $row;
}

/**
 * Create or update expert from a mapped row.
 *
 * @param array $row          Column name => value.
 * @param array $urls_written Reference set of term IDs already given URL meta this run.
 * @return string|WP_Error 'created' or 'updated' on success, WP_Error otherwise.
 */
function ut_experts_import_row( $row, &$urls_written ) {
	$title = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
	if ( '' === $title ) {
		return new WP_Error( 'no_title', __( 'missing title.', 'ut-experts' ) );
	}

	$id      = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
	$is_new  = true;
	$postarr = array(
		'post_type'    => 'expert',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => isset( $row['bio'] ) ? wp_kses_post( $row['bio'] ) : '',
		'post_excerpt' => isset( $row['excerpt'] ) ? sanitize_textarea_field( $row['excerpt'] ) : '',
	);

	if ( $id ) {
		$existing = get_post( $id );
		if ( ! $existing || 'expert' !== $existing->post_type ) {
			return new WP_Error( 'bad_id', sprintf( /* translators: %d: ID */ __( 'no Expert found with ID %d.', 'ut-experts' ), $id ) );
		}
		$postarr['ID'] = $id;
		$is_new        = false;
	}

	$post_id = wp_insert_post( $postarr, true );
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// ACF post fields (fall back to post meta if no ACF).
	ut_experts_set_field( 'field_expert_title', isset( $row['expert_title'] ) ? sanitize_text_field( $row['expert_title'] ) : '', $post_id, 'expert_title' );
	ut_experts_set_field( 'field_expert_website', isset( $row['website'] ) ? esc_url_raw( $row['website'] ) : '', $post_id, 'expert_website' );

	// Featured image - sideload only if a URL is given and no thumbnail exists yet to prevent duplicates.
	if ( ! empty( $row['photo_url'] ) && ! has_post_thumbnail( $post_id ) ) {
		ut_experts_sideload_thumbnail( esc_url_raw( $row['photo_url'] ), $post_id );
	}

	// Areas of Expertise is a hierarchical taxonomy.
	$area_ids = ut_experts_resolve_hierarchical_terms( 'ut_expert_area_of_expertise', $row['areas_of_expertise'] ?? '' );
	wp_set_object_terms( $post_id, $area_ids, 'ut_expert_area_of_expertise', false );

	// College, department and center are flat taxonomies.
	ut_experts_assign_flat_taxonomy( $post_id, 'ut_expert_college', $row['college'] ?? '', 'field_expert_college_url', $row['college_url'] ?? '', $urls_written );
	ut_experts_assign_flat_taxonomy( $post_id, 'ut_expert_department', $row['department'] ?? '', 'field_expert_department_url', $row['department_url'] ?? '', $urls_written );
	ut_experts_assign_flat_taxonomy( $post_id, 'ut_expert_center', $row['center'] ?? '', 'field_expert_center_url', $row['center_url'] ?? '', $urls_written );

	return $is_new ? 'created' : 'updated';
}

/**
 * Set an ACF field by key, or fall back to post meta if ACF isn't active.
 *
 * @param string $field_key ACF field key.
 * @param mixed  $value     Value to store.
 * @param int    $post_id   Target post ID.
 * @param string $meta_key  Fallback meta key when ACF is unavailable.
 */
function ut_experts_set_field( $field_key, $value, $post_id, $meta_key ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( $field_key, $value, $post_id );
	} else {
		update_post_meta( $post_id, $meta_key, $value );
	}
}

/**
 * Resolve a pipe-separated list of hierarchical terms.
 *
 * Each value accepts "Parent > Child > Grandchild" format.
 *
 * @param string $taxonomy Taxonomy slug.
 * @param string $value    Raw column value.
 * @return int[] Leaf term IDs.
 */
function ut_experts_resolve_hierarchical_terms( $taxonomy, $value ) {
	$ids = array();
	if ( '' === trim( $value ) ) {
		return $ids;
	}

	foreach ( explode( '|', $value ) as $path ) {
		$segments = array_filter( array_map( 'trim', explode( '>', $path ) ), 'strlen' );
		if ( empty( $segments ) ) {
			continue;
		}

		$parent_id = 0;
		$term_id   = 0;
		foreach ( $segments as $name ) {
			$name = sanitize_text_field( $name );
			$term = get_term_by( 'name', $name, $taxonomy );

			// Match within the current parent if one exists.
			if ( $term && (int) $term->parent === (int) $parent_id ) {
				$term_id = (int) $term->term_id;
			} else {
				$new = wp_insert_term( $name, $taxonomy, array( 'parent' => $parent_id ) );
				if ( is_wp_error( $new ) ) {
					// If it already exists (e.g. same name, different branch), reuse it.
					$existing = get_term_by( 'name', $name, $taxonomy );
					$term_id  = $existing ? (int) $existing->term_id : 0;
				} else {
					$term_id = (int) $new['term_id'];
				}
			}

			if ( ! $term_id ) {
				break;
			}
			$parent_id = $term_id;
		}

		if ( $term_id ) {
			$ids[] = $term_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Resolve a pipe-separated list of flat term names.
 *
 * @param string $taxonomy Taxonomy slug.
 * @param string $value    Raw column value.
 * @return int[] Term IDs.
 */
function ut_experts_resolve_flat_terms( $taxonomy, $value ) {
	$ids = array();
	if ( '' === trim( $value ) ) {
		return $ids;
	}

	foreach ( explode( '|', $value ) as $name ) {
		$name = sanitize_text_field( trim( $name ) );
		if ( '' === $name ) {
			continue;
		}
		$term = get_term_by( 'name', $name, $taxonomy );
		if ( $term ) {
			$ids[] = (int) $term->term_id;
		} else {
			$new = wp_insert_term( $name, $taxonomy );
			if ( ! is_wp_error( $new ) ) {
				$ids[] = (int) $new['term_id'];
			}
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Assign a flat taxonomy to a post and set the term-meta URL on the primary term.
 *
 * The URL is written to the first resolved term and only once per import run.
 * A blank URL column leaves any existing term meta untouched.
 *
 * @param int    $post_id      Post to assign terms to.
 * @param string $taxonomy     Taxonomy slug.
 * @param string $names        Raw column value (term names).
 * @param string $url_field    ACF field key for the term URL.
 * @param string $url          Raw URL column value.
 * @param array  $urls_written Reference set of term IDs already updated this run.
 */
function ut_experts_assign_flat_taxonomy( $post_id, $taxonomy, $names, $url_field, $url, &$urls_written ) {
	$ids = ut_experts_resolve_flat_terms( $taxonomy, $names );
	wp_set_object_terms( $post_id, $ids, $taxonomy, false );

	$url = trim( $url );
	if ( '' === $url || empty( $ids ) ) {
		return;
	}

	$primary = $ids[0];
	if ( isset( $urls_written[ $taxonomy ][ $primary ] ) ) {
		return; // Already wrote this term's URL this run.
	}

	$acf_term_ref = $taxonomy . '_' . $primary; // ACF term selector, e.g. ut_expert_college_42.
	if ( function_exists( 'update_field' ) ) {
		update_field( $url_field, esc_url_raw( $url ), $acf_term_ref );
	} else {
		update_term_meta( $primary, ltrim( $url_field, 'field_' ), esc_url_raw( $url ) );
	}

	$urls_written[ $taxonomy ][ $primary ] = true;
}

/**
 * Sideload an image from URL and set it as the post thumbnail.
 *
 * @param string $url     Image URL.
 * @param int    $post_id Post ID.
 * @return void
 */
function ut_experts_sideload_thumbnail( $url, $post_id ) {
	if ( '' === $url ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $url, $post_id, null, 'id' );
	if ( ! is_wp_error( $attachment_id ) ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}
}