<?php
/**
 * Setup class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Admin;

use OpenAsset\Admin\CustomPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Setup
 */
class Setup {
	/**
	 * Post types.
	 *
	 * @var array
	 */
	private $post_types = array( 'employee', 'project' );


	/**
	 * Post types.
	 *
	 * @var array
	 */
	private $taxonomies = array(
		array(
			'slug'       => 'keyword',
			'singular'   => 'Keyword',
			'plural'     => 'Keywords',
			'post_types' => array( 'oa-project' ),
		),
	);

	/**
	 * Options key.
	 */
	const OPTION_SETTINGS_KEY = 'openasset_settings';
	const OPTION_DATA_KEY     = 'openasset_data';

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Register Custom Post Types.
		add_action( 'init', array( $this, 'create_custom_post_types' ) );

		// Add custom sort to employee and project post types when posts are loaded.
		add_action( 'pre_get_posts', array( $this, 'sort_custom_post_types_archive' ) );

		// Filter projects by keywords.
		add_action( 'pre_get_posts', array( $this, 'filter_projects_by_keywords' ) );

		// Add custom search when searching employee and project post types.
		add_action( 'pre_get_posts', array( $this, 'custom_archive_search' ) );

	}

	/**
	 * Create Custom Post Types.
	 *
	 * @return void
	 */
	public function create_custom_post_types() {
		$cpt = new CustomPostTypes();

		foreach ( $this->post_types as $post_type ) {
			$cpt->create_custom_post_type( $post_type );
		}

		foreach ( $this->taxonomies as $taxonomy ) {
			$cpt->create_custom_taxonomy( $taxonomy['slug'], $taxonomy['singular'], $taxonomy['plural'], $taxonomy['post_types'] );
		}

		flush_rewrite_rules();
	}

	/**
	 * Modify the main query on the 'oa-employee' and 'oa-project' post type archives
	 * to sort posts by the 'sort_value' post meta value.
	 *
	 * @param \WP_Query $query The WordPress Query object.
	 */
	public function sort_custom_post_types_archive( $query ) {
		if ( ! is_admin() && $query->is_main_query() ) {
			// Check if on the 'oa-employee' or 'oa-project' post type archive.
			if ( $query->is_post_type_archive( 'oa-employee' ) || $query->is_post_type_archive( 'oa-project' ) ) {
				$settings = get_option( self::OPTION_SETTINGS_KEY );
				$data     = get_option( self::OPTION_DATA_KEY );

				$order   = 'DESC';  // Default order.
				$orderby = 'meta_value';  // Default to meta_value for string sorting.

				if ( ! empty( $settings ) && ! empty( $settings['data-options'] ) ) {
					$post_type = $query->is_post_type_archive( 'oa-employee' ) ? 'employee' : 'project';

					// Get order from settings.
					$order = $settings['data-options'][ $post_type . '-order-by' ];

					// Get the field to determine sort type.
					$field_id  = isset( $settings['data-options'][ $post_type . '-sort-by' ] ) ? (int) $settings['data-options'][ $post_type . '-sort-by' ] : 0;
					$fields    = isset( $data['fields'][ $post_type ] ) ? $data['fields'][ $post_type ] : array();

					$sort_by_rest_code_name = '';
					$field_type = 'string'; // Default field type.

					foreach ( $fields as $field ) {
						if ( $field_id === $field['id'] ) {
							$sort_by_rest_code_name = $field['rest_code'];
							// Determine the field type. Adjust this based on how your field data is structured.
							if ( isset( $field['field_display_type'] ) ) {
								$field_type = $field['field_display_type']; // e.g., 'string', 'number', 'date', etc.
							}
							break;
						}
					}

					// Set the correct 'orderby' parameter based on the field type.
					if ( in_array( $field_type, array( 'date', 'boolean' ) ) ) {
						$orderby = 'meta_value_num';
					} else {
						$orderby = 'meta_value';
					}
				}

				$query->set( 'meta_key', 'sort_value' );
				$query->set( 'order', $order );
				$query->set( 'orderby', $orderby );
			}
		}
	}


	/**
	 * Filter projects by selected keywords.
	 *
	 * @param \WP_Query $query The WordPress Query object.
	 */
	public function filter_projects_by_keywords( $query ) {
		if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'oa-project' ) ) {

			// Check nonce.
			if ( ! isset( $_GET['keyword_filters_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['keyword_filters_nonce'] ) ), 'keyword_filters_nonce' ) ) {
				return;  // If nonce is not set or invalid, exit early.
			}
			if ( isset( $_GET['keywordfilters'] ) && is_array( $_GET['keywordfilters'] ) ) {

				$tax_queries = array();

				// sanitize array.
				$keywords = array_map( 'sanitize_text_field', wp_unslash( $_GET['keywordfilters'] ) );

				foreach ( $keywords as $keyword ) {
					$tax_queries[] = array(
						'taxonomy' => 'keyword',
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $keyword ),
					);
				}

				if ( ! empty( $tax_queries ) ) {
					$tax_queries['relation'] = 'AND';

					$query->set( 'tax_query', $tax_queries );
				}
			}
		}
	}

	/**
	 * Modify the main query on the 'oa-employee' and 'oa-project' post type archives
	 * to sort posts by the 'sort_value' post meta value.
	 *
	 * @param \WP_Query $query The WordPress Query object.
	 */
	public function custom_archive_search( $query ) {
		// Ensure this is the main query, it's on the front-end, and it's an archive page.
		if ( $query->is_main_query() && ! is_admin() && ( $query->is_post_type_archive( 'oa-employee' ) || $query->is_post_type_archive( 'oa-project' ) ) ) {
			// Check if the nonce and 's' parameter are set.
			if ( isset( $_GET['_wpnonce'], $_GET['s'] ) ) {
				// Unslash and then verify the nonce.
				$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
				if ( wp_verify_nonce( $nonce, 'openasset_custom_search' ) && ! empty( $_GET['s'] ) ) {
					// Sanitize the search query term.
					$search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );
					// Modify the query to include searching post content and title.
					$query->set( 's', $search_query );
				}
			}
		}
	}
}
