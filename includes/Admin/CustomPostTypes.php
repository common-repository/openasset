<?php
/**
 * Plugin Cutom Post Types class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Admin;

/**
 * Generate Custom Post Types.
 */
class CustomPostTypes {

	/**
	 * Options key.
	 */
	const OPTION_SETTINGS_KEY = 'openasset_settings';

	/**
	 * Create Custom Post Type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return void
	 */
	public function create_custom_post_type( string $post_type ) {

		$settings = get_option( self::OPTION_SETTINGS_KEY );

		if ( empty( $settings ) || empty( $settings['general-settings'] ) ) {
			return;
		}

		$singular = isset( $settings['general-settings'][ $post_type . '-name-singular' ] ) ? $settings['general-settings'][ $post_type . '-name-singular' ] : '';
		$plural   = isset( $settings['general-settings'][ $post_type . '-name-plural' ] ) ? $settings['general-settings'][ $post_type . '-name-plural' ] : '';

		if ( ! $singular || ! $plural ) {
			return;
		}

		$url_key = isset( $settings['general-settings'][ $post_type . '-url-key' ] ) && $settings['general-settings'][ $post_type . '-url-key' ] ? strtolower( $settings['general-settings'][ $post_type . '-url-key' ] ) : $post_type;

		$show = isset( $settings['general-settings'][ $post_type . '-show' ] ) ? $settings['general-settings'][ $post_type . '-show' ] : 'no';
		$show = 'yes' === $show ? true : false;

		$labels = array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'name_admin_bar'     => $singular,
			'add_new'            => 'Add New ' . $singular,
			'add_new_item'       => 'Add New ' . $singular,
			'new_item'           => 'New ' . $singular,
			'edit_item'          => 'Edit ' . $singular,
			'view_item'          => 'View ' . $singular,
			'all_items'          => 'All ' . $plural,
			'search_items'       => 'Search ' . $plural,
			'parent_item_colon'  => 'Parent ' . $plural . ':',
			'not_found'          => 'No ' . strtolower( $plural ) . ' found.',
			'not_found_in_trash' => 'No ' . strtolower( $plural ) . ' found in Trash.',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => $show,
			'publicly_queryable' => true,
			'show_ui'            => $show,
			'show_in_menu'       => $show,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $url_key ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'create_posts' => 'do_not_allow',
			),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		);

		if ( true === $show ) {
			register_post_type( 'oa-' . $post_type, $args );
		}
	}

	/**
	 * Create Custom Taxonomy.
	 *
	 * @param string $slug       Slug.
	 * @param string $singular   Singular.
	 * @param string $plural     Plural.
	 * @param array  $post_types Post types.
	 *
	 * @return void
	 */
	public function create_custom_taxonomy( string $slug, string $singular, string $plural, array $post_types ) {

		if ( ! $singular || ! $plural ) {
			return;
		}

		$labels = array(
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => 'Search ' . $plural,
			'all_items'         => 'All ' . $plural,
			'parent_item'       => 'Parent ' . $singular,
			'parent_item_colon' => 'Parent ' . $singular . ':',
			'edit_item'         => 'Edit ' . $singular,
			'update_item'       => 'Update ' . $singular,
			'add_new_item'      => 'Add New ' . $singular,
			'new_item_name'     => 'New ' . $singular . ' Name',
			'menu_name'         => $plural,
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $slug ),
		);

		register_taxonomy( $slug, $post_types, $args );
	}

}
