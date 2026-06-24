<?php
/**
 * Set Up Expert Data Structure.
 *
 * Register Expert custom post type and necessary taxonomies and fields
 *
 * @package UtkwdsExperts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the Experts custom post type
 */
function ut_experts_register_cpts() {

	$labels = array(
		'name'          => __( 'Experts', 'ut-experts' ),
		'singular_name' => __( 'Expert', 'ut-experts' ),
		'search_items'  => __( 'Search Experts', 'ut-experts' ),
		'all_items'     => __( 'All Experts', 'ut-experts' ),
		'edit_item'     => __( 'Edit Expert', 'ut-experts' ),
		'update_item'   => __( 'Update Expert', 'ut-experts' ),
		'add_new_item'  => __( 'Add Expert', 'ut-experts' ),
		'not_found'     => __( 'No experts found.', 'ut-experts' ),
		'menu_name'     => __( 'Experts', 'ut-experts' ),
	);

	$args = array(
		'labels'                => $labels,
		'description'           => '',
		'public'                => false,
		'publicly_queryable'    => true,
		'show_ui'               => true,
		'show_in_rest'          => true,
		'rest_base'             => '',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
		'rest_namespace'        => 'wp/v2',
		'has_archive'           => false,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'delete_with_user'      => false,
		'exclude_from_search'   => true,
		'capability_type'       => 'post',
		'map_meta_cap'          => true,
		'hierarchical'          => false,
		'can_export'            => false,
		'rewrite'               => false,
		'query_var'             => false,
		'menu_icon'             => 'dashicons-id',
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	);

	register_post_type( 'expert', $args );
}

add_action( 'init', 'ut_experts_register_cpts' );

/**
 * Register taxonomies for Experts post type
 */
function ut_experts_register_taxes() {

	/**
	 * Taxonomy: Areas of Expertise
	 */

	$labels = array(
		'name'                       => __( 'Areas of Expertise', 'ut-experts' ),
		'singular_name'              => __( 'Area of Expertise', 'ut-experts' ),
		'search_items'               => __( 'Search Areas of Expertise', 'ut-experts' ),
		'all_items'                  => __( 'All Areas of Expertise', 'ut-experts' ),
		'edit_item'                  => __( 'Edit Area of Expertise', 'ut-experts' ),
		'update_item'                => __( 'Update Area of Expertise', 'ut-experts' ),
		'add_new_item'               => __( 'Add Area of Expertise', 'ut-experts' ),
		'new_item_name'              => __( 'New Area of Expertise Name', 'ut-experts' ),
		'separate_items_with_commas' => __( 'Separate areas of expertise with commas', 'ut-experts' ),
		'add_or_remove_items'        => __( 'Add or remove areas of expertise', 'ut-experts' ),
		'choose_from_most_used'      => __( 'Choose from the most used areas of expertise', 'ut-experts' ),
		'not_found'                  => __( 'No areas of expertise found.', 'ut-experts' ),
		'menu_name'                  => __( 'Areas of Expertise', 'ut-experts' ),
	);

	$args = array(
		'labels'                => $labels,
		'public'                => false,
		'publicly_queryable'    => true,
		'has_archive'           => false,
		'hierarchical'          => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => false,
		'rewrite'               => array(
			'slug'       => 'ut_expert_area_of_expertise',
			'with_front' => true,
		),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'show_tagcloud'         => false,
		'rest_base'             => 'ut_expert_area_of_expertise',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'rest_namespace'        => 'wp/v2',
		'show_in_quick_edit'    => false,
		'sort'                  => false,
	);
	register_taxonomy( 'ut_expert_area_of_expertise', array( 'expert' ), $args );

	/**
	 * Taxonomy: Colleges
	 */

	$labels = array(
		'name'                       => __( 'Colleges', 'ut-experts' ),
		'singular_name'              => __( 'College', 'ut-experts' ),
		'search_items'               => __( 'Search Colleges', 'ut-experts' ),
		'all_items'                  => __( 'All Colleges', 'ut-experts' ),
		'edit_item'                  => __( 'Edit College', 'ut-experts' ),
		'update_item'                => __( 'Update College', 'ut-experts' ),
		'add_new_item'               => __( 'Add College', 'ut-experts' ),
		'new_item_name'              => __( 'New College Name', 'ut-experts' ),
		'separate_items_with_commas' => __( 'Separate colleges with commas', 'ut-experts' ),
		'add_or_remove_items'        => __( 'Add or remove colleges', 'ut-experts' ),
		'choose_from_most_used'      => __( 'Choose from the most used colleges', 'ut-experts' ),
		'not_found'                  => __( 'No colleges found.', 'ut-experts' ),
		'menu_name'                  => __( 'Colleges', 'ut-experts' ),
	);

	$args = array(
		'labels'                => $labels,
		'public'                => false,
		'publicly_queryable'    => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => false,
		'rewrite'               => array(
			'slug'       => 'ut_expert_college',
			'with_front' => true,
		),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'show_tagcloud'         => false,
		'rest_base'             => 'ut_expert_college',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'rest_namespace'        => 'wp/v2',
		'show_in_quick_edit'    => false,
		'sort'                  => false,
	);
	register_taxonomy( 'ut_expert_college', array( 'expert' ), $args );

	/**
	 * Taxonomy: Departments and Schools
	 */

	$labels = array(
		'name'                       => __( 'Departments and Schools', 'ut-experts' ),
		'singular_name'              => __( 'Department or School', 'ut-experts' ),
		'search_items'               => __( 'Search Departments and Schools', 'ut-experts' ),
		'all_items'                  => __( 'All Departments and Schools', 'ut-experts' ),
		'edit_item'                  => __( 'Edit Department or School', 'ut-experts' ),
		'update_item'                => __( 'Update Department or School', 'ut-experts' ),
		'add_new_item'               => __( 'Add Department or School', 'ut-experts' ),
		'new_item_name'              => __( 'New Department or School Name', 'ut-experts' ),
		'separate_items_with_commas' => __( 'Separate departments and schools with commas', 'ut-experts' ),
		'add_or_remove_items'        => __( 'Add or remove departments and schools', 'ut-experts' ),
		'choose_from_most_used'      => __( 'Choose from the most used departments and schools', 'ut-experts' ),
		'not_found'                  => __( 'No departments or schools found.', 'ut-experts' ),
		'menu_name'                  => __( 'Departments and Schools', 'ut-experts' ),
	);

	$args = array(
		'labels'                => $labels,
		'public'                => false,
		'publicly_queryable'    => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => false,
		'rewrite'               => array(
			'slug'       => 'ut_expert_department',
			'with_front' => true,
		),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'show_tagcloud'         => false,
		'rest_base'             => 'ut_expert_department',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'rest_namespace'        => 'wp/v2',
		'show_in_quick_edit'    => false,
		'sort'                  => false,
	);
	register_taxonomy( 'ut_expert_department', array( 'expert' ), $args );

	/**
	 * Taxonomy: Centers
	 */

	$labels = array(
		'name'                       => __( 'Centers', 'ut-experts' ),
		'singular_name'              => __( 'Center', 'ut-experts' ),
		'search_items'               => __( 'Search Centers', 'ut-experts' ),
		'all_items'                  => __( 'All Centers', 'ut-experts' ),
		'edit_item'                  => __( 'Edit Center', 'ut-experts' ),
		'update_item'                => __( 'Update Center', 'ut-experts' ),
		'add_new_item'               => __( 'Add Center', 'ut-experts' ),
		'new_item_name'              => __( 'New Center Name', 'ut-experts' ),
		'separate_items_with_commas' => __( 'Separate centers with commas', 'ut-experts' ),
		'add_or_remove_items'        => __( 'Add or remove centers', 'ut-experts' ),
		'choose_from_most_used'      => __( 'Choose from the most used centers', 'ut-experts' ),
		'not_found'                  => __( 'No centers found.', 'ut-experts' ),
		'menu_name'                  => __( 'Centers', 'ut-experts' ),
	);

	$args = array(
		'labels'                => $labels,
		'public'                => false,
		'publicly_queryable'    => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => false,
		'rewrite'               => array(
			'slug'       => 'ut_expert_center',
			'with_front' => true,
		),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'show_tagcloud'         => false,
		'rest_base'             => 'ut_expert_center',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'rest_namespace'        => 'wp/v2',
		'show_in_quick_edit'    => false,
		'sort'                  => false,
	);
	register_taxonomy( 'ut_expert_center', array( 'expert' ), $args );
}
add_action( 'init', 'ut_experts_register_taxes' );

/**
 * Add custom fields to Area of Study taxonomy, Program post type, Unit post type.
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		array(
			'key'                   => 'group_expert_details',
			'title'                 => 'Expert Details',
			'fields'                => array(
				array(
					'key'               => 'field_expert_title',
					'label'             => 'Title',
					'name'              => 'expert_title',
					'type'              => 'text',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
				),
				array(
					'key'               => 'field_expert_website',
					'label'             => 'Website',
					'name'              => 'expert_website',
					'type'              => 'url',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'expert',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_rest'          => 1,
		)
	);

	acf_add_local_field_group(
		array(
			'key'          => 'group_expert_college_details',
			'title'        => 'College Details',
			'fields'       => array(
				array(
					'key'               => 'field_expert_college_url',
					'label'             => 'College Website',
					'name'              => 'expert_college_url',
					'type'              => 'url',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
				),
			),
			'location'     => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'ut_expert_college',
					),
				),
			),
			'active'       => true,
			'show_in_rest' => 1,
		)
	);

	acf_add_local_field_group(
		array(
			'key'          => 'group_expert_department_details',
			'title'        => 'Department or School Details',
			'fields'       => array(
				array(
					'key'               => 'field_expert_department_url',
					'label'             => 'Department or School Website',
					'name'              => 'expert_department_url',
					'type'              => 'url',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
				),
			),
			'location'     => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'ut_expert_department',
					),
				),
			),
			'active'       => true,
			'show_in_rest' => 1,
		)
	);

	acf_add_local_field_group(
		array(
			'key'          => 'group_expert_center_details',
			'title'        => 'Center Details',
			'fields'       => array(
				array(
					'key'               => 'field_expert_center_url',
					'label'             => 'Center Website',
					'name'              => 'expert_center_url',
					'type'              => 'url',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
				),
			),
			'location'     => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'ut_expert_center',
					),
				),
			),
			'active'       => true,
			'show_in_rest' => 1,
		)
	);

	endif;
