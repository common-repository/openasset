<?php
/**
 * Settings class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Admin;

use OpenAsset\API\OpenAssetAPI;
use OpenAsset\Admin\ApiHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {
	/**
	 * Post types.
	 *
	 * @var array
	 */
	private $post_types = array( 'employee', 'project' );

	/**
	 * OpenAsset API manager.
	 *
	 * @var OpenAssetAPI
	 */
	public $open_asset_api_manager;

	/**
	 * Options key.
	 */
	const OPTION_SETTINGS_KEY = 'openasset_settings';
	const OPTION_DATA_KEY     = 'openasset_data';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->open_asset_api_manager = new OpenAssetAPI();
	}

	/**
	 * Update all keywords from OpenAsset in Keywords custom Taxonomy.
	 *
	 * @return void
	 */
	public function sync_keywords_with_open_asset() {
		$data = get_option( self::OPTION_DATA_KEY );

		if ( empty( $data ) ) {
			return;
		}

		$taxonomy = 'keyword';  // your custom taxonomy slug.

		$parent_keywords = isset( $data['project-keyword-categories'] ) ? $data['project-keyword-categories'] : array();
		$child_keyword_items  = isset( $data['project-keywords'] ) ? $data['project-keywords'] : array();

		// Extract the IDs of child keywords.
		$ids = array_map( function( $item ) {
			return $item['id'];
		}, $child_keyword_items );

		// Build query to fetch child keywords from OpenAsset.
		$id_string = implode( ',', $ids );
		$query_params = array(
			'limit'   => 0,
			'id'      => "{$id_string}",
			'orderBy' => 'project_countDesc',
		);
		$query_string = http_build_query( $query_params );
		$url = "/ProjectKeywords?{$query_string}";

		$child_keywords = $this->open_asset_api_manager->return_data( $url );

		// Build array of terms from OpenAsset.
		$third_party_terms = array();

		foreach ( $parent_keywords as $parent ) {
			foreach ( $child_keywords as $child ) {
				if ( $child['project_keyword_category_id'] === $parent['id'] ) {
					$third_party_terms[] = array(
						'parent'    => $parent['name'],
						'parent_id' => $parent['id'],
						'child'     => $child['name'],
						'child_id'  => $child['id'],
					);
				}
			}
		}

		// Fetch existing terms from WordPress.
		$existing_terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );

		// Build an array of current OpenAsset term slugs.
		$current_term_slugs = array();
		foreach ( $third_party_terms as $term_data ) {
			$parent_slug = sanitize_title( $term_data['parent'] );
			$child_slug  = sanitize_title( $term_data['child'] );

			$current_term_slugs[ $parent_slug ] = true;
			$current_term_slugs[ $child_slug ] = true;
		}

		// Delete terms that are no longer in OpenAsset.
		foreach ( $existing_terms as $term ) {
			if ( ! isset( $current_term_slugs[ $term->slug ] ) ) {
				// Term no longer exists in OpenAsset, so delete it.
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}

		// Insert or update terms from OpenAsset.
		foreach ( $third_party_terms as $term_data ) {
			$parent_term_name = $term_data['parent'];
			$child_term_name  = $term_data['child'];

			// Ensure parent term exists.
			$parent_term = term_exists( $parent_term_name, $taxonomy );
			if ( ! $parent_term ) {
				$parent_term = wp_insert_term( $parent_term_name, $taxonomy );
				if ( ! is_wp_error( $parent_term ) && isset( $term_data['parent_id'] ) ) {
					add_term_meta( $parent_term['term_id'], 'openasset_id', $term_data['parent_id'], true );
				}
			}

			// Get the parent term ID.
			$parent_term    = get_term_by( 'name', $parent_term_name, $taxonomy );
			$parent_term_id = $parent_term->term_id;

			// Ensure child term exists.
			$child_term = term_exists( $child_term_name, $taxonomy );
			if ( ! $child_term ) {
				$child_term = wp_insert_term( $child_term_name, $taxonomy, array( 'parent' => $parent_term_id ) );
				if ( ! is_wp_error( $child_term ) && isset( $term_data['child_id'] ) ) {
					add_term_meta( $child_term['term_id'], 'openasset_id', $term_data['child_id'], true );
					add_term_meta( $child_term['term_id'], 'openasset_parent_id', $term_data['parent_id'], true );
				}
			} else {
				// Update parent term if necessary.
				wp_update_term( $child_term['term_id'], $taxonomy, array( 'parent' => $parent_term_id ) );
			}
		}
	}

	/**
	 * Update all keywords from OpenAsset on the individual post.
	 *
	 * @param int/string $post_id    The post ID.
	 * @param array      $terms_data The terms data.
	 *
	 * @return void
	 */
	public function update_post_terms( $post_id, $terms_data ) {
		$taxonomy = 'keyword';  // your custom taxonomy slug
		$term_ids = array();

		foreach ( $terms_data as $term_data ) {
			$parent_term_name = $term_data['parent'];
			$child_term_name = $term_data['child'];

			// Get parent and child term IDs
			$parent_term = get_term_by( 'name', $parent_term_name, $taxonomy );
			$child_term = get_term_by( 'name', $child_term_name, $taxonomy );

			if ( $parent_term ) {
				$term_ids[] = $parent_term->term_id;
			}
			if ( $child_term ) {
				$term_ids[] = $child_term->term_id;
			}
		}

		// Set post terms
		wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
	}

	/**
	 * Use OpenAsset keyword id to lookup the associated keyword custom taxonomy data.
	 *
	 * @param int/string $child_id  The openasset keyword ID.
	 * @param string     $taxonomy Name of custom taxonomy.
	 *
	 * @return \Term|null
	 */
	public function get_term_by_child_id( $child_id, $taxonomy ) {
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'   => 'openasset_id',
					'value' => $child_id,
				),
			),
		);

		$terms = get_terms( $args );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0];
		}

		return null;
	}

	/**
	 * Add OpenAsset keywords to post by id.
	 *
	 * @param int/string $post_id    The post ID.
	 * @param array      $project_keywords Array of objects holding each keyword indicated by the openasset keyword id.
	 *
	 * @return void
	 */
	public function update_post_terms_by_child_ids( $post_id, $project_keywords ) {
		$taxonomy = 'keyword';  // Your custom taxonomy slug.

		$child_ids = array();

		// Extract OpenAsset keyword IDs from the project keywords.
		foreach ( $project_keywords as $project_keyword ) {
			$child_ids[] = $project_keyword['id'];
		}

		$term_ids = array();

		// Find corresponding WordPress term IDs for the OpenAsset keyword IDs.
		foreach ( $child_ids as $child_id ) {
			$term = $this->get_term_by_child_id( $child_id, $taxonomy );

			if ( $term ) {
				$term_ids[] = $term->term_id;
			}
		}

		// Always update the post's terms, even if $term_ids is empty.
		// This ensures that removed keywords are also removed from the post.
		wp_set_post_terms( $post_id, $term_ids, $taxonomy, false ); // 'false' replaces existing terms.
	}

	/**
	 * Handle Posts.
	 *
	 * @return void
	 */
	public function handle_posts() {

		// Capture start time.
		$start_time = time();

		// Define your maximum execution time (in seconds).
		$max_execution_time = 50;

		$settings = get_option( self::OPTION_SETTINGS_KEY );
		$data     = get_option( self::OPTION_DATA_KEY );

		if ( empty( $settings ) || empty( $settings['general-settings'] ) ) {
			return;
		}

		$api_helpers = new ApiHelpers();

		foreach ( $this->post_types as $post_type ) {
			$url = $api_helpers->generate_request_url( $post_type );

			$all_oa_posts = $this->open_asset_api_manager->return_data( $url );

			update_option( 'openasset_total_' . $post_type . '_count', count( $all_oa_posts ) );
		}

		foreach ( $this->post_types as $post_type ) {
			$show = isset( $settings['general-settings'][ $post_type . '-show' ] ) ? $settings['general-settings'][ $post_type . '-show' ] : 'no';

			$all_wp_posts = get_posts(
				array(
					'post_type'      => 'oa-' . $post_type,
					'posts_per_page' => -1,
				)
			);

			$url = $api_helpers->generate_request_url( $post_type );

			$all_oa_posts = $this->open_asset_api_manager->return_data( $url );

			if ( null === $all_oa_posts || ! is_array( $all_oa_posts ) ) {
				// Error getting posts from OpenAsset.
				return;
			}

			$total_count = get_option( 'openasset_total_' . $post_type . '_count' );

			if ( count( $all_oa_posts ) !== $total_count ) {
				update_option( 'openasset_total_' . $post_type . '_count', count( $all_oa_posts ) );
			}

			if ( 'no' === $show ) {
				$all_oa_posts = array();
			}

			$last_updated = get_option( 'openasset_last_' . $post_type . '_updated' );
			if ( null === $last_updated || '' === $last_updated || empty( $last_updated ) ) {
				$last_updated = -1;
			}

			// if ( ! $last_updated || $last_updated < 0 ) {
			// add all project keywords to data.
			if ( 'project' === $post_type ) {
				$keywords = array();
				foreach ( $all_oa_posts as $oa_post ) {
					$keywords = array_merge( $keywords, $oa_post['projectKeywords'] );
				}
				// remove duplicates from $keywords.
				$keywords = array_unique( $keywords, SORT_REGULAR );

				$openasset_data = get_option( 'openasset_data' );

				$openasset_data['project-keywords'] = $keywords;

				update_option( 'openasset_data', $openasset_data );
				// }

				// Sync keywords.
				$this->sync_keywords_with_open_asset();
			}

			foreach ( $all_oa_posts as $index => $oa_post ) {
				

				$args = array(
					'post_type'      => 'oa-' . $post_type,
					'posts_per_page' => 1,
					'meta_key'       => 'openasset_id',
					'meta_value'     => $oa_post['id'],
				);

				$wp_post = get_posts( $args );

				// If post is found, update it.
				if ( isset( $wp_post[0] ) && $wp_post[0] ) {
					// Unset the found post from the all wp posts array.
					foreach ( $all_wp_posts as $key => $post ) {
						if ( $post->ID == $wp_post[0]->ID ) {
							unset( $all_wp_posts[ $key ] );
							break; // exit the loop if you found the target post.
						}
					}

					// skip posts we have already updated.
					if ( $index <= $last_updated ) {
						continue;
					}

					if ( 'employee' === $post_type ) {
						$first_name = isset( $oa_post['first_name'] ) ? $oa_post['first_name'] : '';
						$last_name  = isset( $oa_post['last_name'] ) ? $oa_post['last_name'] : '';
						$job_title  = isset( $oa_post['job_title'] ) ? $oa_post['job_title'] : '';

						$post_title   = $first_name . ' ' . $last_name;
						$post_content = $job_title;
					} else {
						$name = isset( $oa_post['name'] ) ? $oa_post['name'] : '';
						$code = isset( $oa_post['code'] ) ? $oa_post['code'] : '';

						$post_title   = $name;
						$post_content = $code;
					}

					// Update the post data.
					wp_update_post(
						array(
							'ID'           => $wp_post[0]->ID,
							'post_title'   => $post_title,
							'post_content' => $post_content,
						)
					);

					// Update post meta data.
					update_post_meta( $wp_post[0]->ID, 'openasset_data', $oa_post );

					// Add hero image as post featured image.
					$this->add_hero_image_to_post( $wp_post[0]->ID, $oa_post['hero_image_id'] );

					// Add all other images to the post.
					$post_image_limit = $settings['data-options'][ 'maximum-images-per-' . $post_type ];
					$show_on_wesbite  = $settings['data-options'][ $post_type . '-images-tagged-show-on-website' ];
					$this->add_all_images_to_post( $post_type, $wp_post[0]->ID, $oa_post['id'], $post_image_limit, $show_on_wesbite, $oa_post['hero_image_id'] );

					// Add keywords to the post if it is a project.
					if ( 'project' === $post_type ) {
						$this->update_post_terms_by_child_ids( $wp_post[0]->ID, $oa_post['projectKeywords'] );
					}
					// Add sort value to post.
					$field_id = isset( $settings['data-options'][ $post_type . '-sort-by' ] ) ? (int) $settings['data-options'][ $post_type . '-sort-by' ] : 0;
					$fields   = isset( $data['fields'][ $post_type ] ) ? $data['fields'][ $post_type ] : array();

					$sort_by_rest_code_name = '';

					foreach ( $fields as $field ) {
						if ( $field_id === $field['id'] ) {
							$sort_by_rest_code_name = $field['rest_code'];
							break;
						}
					}

					if ( '' === $sort_by_rest_code_name ) {
						$sort_by_rest_code_name = 'code';
					}

					if ( 'employee' === $post_type ) {
						$sort_value = isset( $oa_post[ $sort_by_rest_code_name ] ) ? $oa_post[ $sort_by_rest_code_name ] : '';
					} else {
						if ( 'name' === $sort_by_rest_code_name ) {
							$sort_value = $oa_post['name'];
						} elseif ( 'code' === $sort_by_rest_code_name ) {
							$sort_value = $oa_post['code'];
						} else {
							foreach ( $oa_post['fields'] as $field ) {
								if ( $field_id === $field['id'] ) {
									$sort_value = $field['values'][0];
									break;
								}
							}
						}
					}

					update_post_meta( $wp_post[0]->ID, 'sort_value', $sort_value );
				} else {
					// skip posts we have already updated.
					if ( $index <= $last_updated ) {
						continue;
					}
					if ( 'employee' === $post_type ) {
						$first_name = isset( $oa_post['first_name'] ) ? $oa_post['first_name'] : '';
						$last_name  = isset( $oa_post['last_name'] ) ? $oa_post['last_name'] : '';
						$job_title  = isset( $oa_post['job_title'] ) ? $oa_post['job_title'] : '';

						$post_title   = $first_name . ' ' . $last_name;
						$post_content = $job_title;
					} else {
						$name = isset( $oa_post['name'] ) ? $oa_post['name'] : '';
						$code = isset( $oa_post['code'] ) ? $oa_post['code'] : '';

						$post_title   = $name;
						$post_content = $code;
					}

					// Create a new $post_type post.
					$post_id = wp_insert_post(
						array(
							'post_type'    => 'oa-' . $post_type,
							'post_title'   => $post_title,
							'post_status'  => 'publish',
							'post_content' => $post_content,
						)
					);

					if ( OPENASSET_ENABLE_LOGGING ) {
						error_log( 'exact time: ' . gmdate( 'y-m-d h:i:s', time() ) );
						error_log( 'open asset id: ' . $oa_post['id'] );
					}


					// Check for errors.
					if ( $post_id && ! is_wp_error( $post_id ) ) {
						// Add all additional data as post meta.
						add_post_meta( $post_id, 'openasset_id', $oa_post['id'] );
						add_post_meta( $post_id, 'openasset_data', $oa_post );

						// Add hero image as post featured image.
						$this->add_hero_image_to_post( $post_id, $oa_post['hero_image_id'] );

						// Add all other images to the post.
						$post_image_limit = $settings['data-options'][ 'maximum-images-per-' . $post_type ];
						$show_on_wesbite  = $settings['data-options'][ $post_type . '-images-tagged-show-on-website' ];
						$this->add_all_images_to_post( $post_type, $post_id, $oa_post['id'], $post_image_limit, $show_on_wesbite, $oa_post['hero_image_id'] );

						// Add keywords to the post if it is a project.
						if ( 'project' === $post_type ) {
							$this->update_post_terms_by_child_ids( $post_id, $oa_post['projectKeywords'] );
						}

						// Add sort value to post.
						$field_id = (int) isset( $settings['data-options'][ $post_type . '-sort-by' ] ) ? (int) $settings['data-options'][ $post_type . '-sort-by' ] : 0;
						$fields   = isset( $data['fields'][ $post_type ] ) ? $data['fields'][ $post_type ] : array();

						$sort_by_rest_code_name = '';

						foreach ( $fields as $field ) {
							if ( $field_id === $field['id'] ) {
								$sort_by_rest_code_name = $field['rest_code'];
								break;
							}
						}

						if ( '' === $sort_by_rest_code_name ) {
							$sort_by_rest_code_name = 'code';
						}

						if ( 'employee' === $post_type ) {
							$sort_value = isset( $oa_post[ $sort_by_rest_code_name ] ) ? $oa_post[ $sort_by_rest_code_name ] : '';
						} else {
							if ( 'name' === $sort_by_rest_code_name ) {
								$sort_value = $oa_post['name'];
							} elseif ( 'code' === $sort_by_rest_code_name ) {
								$sort_value = $oa_post['code'];
							} else {
								foreach ( $oa_post['fields'] as $field ) {
									if ( $field_id === $field['id'] ) {
										$sort_value = $field['values'][0];
										break;
									}
								}
							}
						}

						update_post_meta( $post_id, 'sort_value', $sort_value );
					} else {
						// Handle the error according to your logging or notification preference.
						if ( OPENASSET_ENABLE_LOGGING ) {
							error_log( 'Unable to create post for employee id: ' . $oa_post['id'] );
						}
					}
				}
				// index number.
				update_option( 'openasset_last_' . $post_type . '_updated', $index );

				// Check elapsed time.
				$elapsed_time = time() - $start_time;

				// If the elapsed time is greater than or equal to the max execution time, return.
				if ( $elapsed_time >= $max_execution_time ) {
					if ( OPENASSET_ENABLE_LOGGING ) {
						error_log( 'Time limit reached. Function is returning early' );
					}
					return; // Exiting the function.
				}
			}

			$posts_deleted = get_option( 'openasset_' . $post_type . '_posts_deleted' );
			if ( null === $posts_deleted || '' === $posts_deleted || empty( $posts_deleted ) ) {
				// Delete old wp posts that are no longer in OpenAsset.
				foreach ( $all_wp_posts as $post ) {
					// Get all attachments of the post.
					$attachments = get_attached_media( 'image', $post->ID );
					// Loop through each attachment and delete it.
					foreach ( $attachments as $attachment ) {
						wp_delete_attachment( $attachment->ID, true );
					}
					// Forcefully delete the post without moving it to Trash.
					$deleted = wp_delete_post( $post->ID, true );
				}

				// Set posts_deleted to 1 to indicate that all posts have been deleted.
				update_option( 'openasset_' . $post_type . '_posts_deleted', 1 );

			}
		}

		// All posts have been updated.
		update_option( 'openasset_sync_running', 0 );
		delete_option( 'openasset_last_employee_updated' );
		delete_option( 'openasset_last_project_updated' );
		delete_option( 'openasset_employee_posts_deleted' );
		delete_option( 'openasset_project_posts_deleted' );
		delete_option( 'openasset_total_employee_count' );
		delete_option( 'openasset_total_project_count' );

		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Syncing posts complete' );
		}
	}

	/**
	 * Add hero image as post featured image.
	 *
	 * @param int $post_id Post ID.
	 * @param int $hero_image_id Hero image ID.
	 * @return void
	 */
	public function add_hero_image_to_post( $post_id, $hero_image_id ) {
		$get_hero_image_data = $this->open_asset_api_manager->get_hero_image_data( $hero_image_id );

		// Check if $get_hero_image_data['sizes'] is set and is an array.
		if ( isset( $get_hero_image_data['sizes'] ) && is_array( $get_hero_image_data['sizes'] ) ) {
			$largest_image = $this->get_largest_image( $get_hero_image_data['sizes'] );
		} else {
			// Handle the case where 'sizes' is not set or not an array.
			$largest_image = null; // or any other default value or error handling.
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( 'Invalid or missing image sizes in $get_hero_image_data.' );
			}
		}

		if ( $largest_image ) {
			$full_image_url = 'https:' . $largest_image['http_root'] . $largest_image['http_relative_path'];
			// Now $full_image_url contains the URL of the largest image by area.
		} else {
			// Handle the case where $sizes is empty or does not contain any valid arrays.
			$full_image_url = null;
		}

		if ( $full_image_url ) {
			unset( $get_hero_image_data['sizes'] );
			unset( $get_hero_image_data['id'] );
			$this->add_single_image_to_media_library( $hero_image_id, $full_image_url, $post_id, true, $get_hero_image_data );
		}
	}

	/**
	 * Add all images to post.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id Post ID.
	 * @param int    $openasset_id OpenAsset ID.
	 * @param int    $image_limit Image limit.
	 * @param string $show_on_wesbite Show on website.
	 * @param int    $hero_image_id Hero image ID.
	 * @return void
	 */
	public function add_all_images_to_post( $post_type, $post_id, $openasset_id, $image_limit, $show_on_wesbite, $hero_image_id ) {
		$all_image_data = $this->open_asset_api_manager->get_all_image_data( $post_type, $openasset_id, $image_limit, $show_on_wesbite );

		// Get the ID of the post's featured image.
		$featured_image_id = get_post_thumbnail_id( $post_id );

		// Remove the hero image from the array.
		foreach ( $all_image_data as $key => $image_data ) {
			if ( $image_data['id'] == $hero_image_id ) {
				unset( $all_image_data[ $key ] );
			}
		}

		// if array is longer than $image_limit converted to int then remove last item.
		if ( count( $all_image_data ) > (int) $image_limit ) {
			array_pop( $all_image_data );
		}

		// Get all existing image IDs attached to the post.
		$existing_images = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'post_parent'    => $post_id,
				'fields'         => 'ids', // Retrieve only the IDs for performance.
				'exclude'        => array( $featured_image_id ),
				'meta_key'       => 'openasset_file_id'
			)
		);

		// Get IDs from $all_image_data for comparison.
		$new_image_ids = array_column( $all_image_data, 'id' );

		// Determine which existing images are not in the new set.
		foreach ( $existing_images as $existing_image_id ) {
			$openasset_file_id = get_post_meta( $existing_image_id, 'openasset_file_id', true );
			if ( ! in_array( $openasset_file_id, $new_image_ids ) ) {
				// Image not in the new set, delete it.
				wp_delete_attachment( $existing_image_id, true );
			}
		}

		foreach ( $all_image_data as $image_data ) {
			$largest_image = $this->get_largest_image( $image_data['sizes'] );

			// Check if $get_hero_image_data['sizes'] is set and is an array.
			if ( isset( $image_data['sizes'] ) && is_array( $image_data['sizes'] ) ) {
				$largest_image = $this->get_largest_image( $image_data['sizes'] );
			} else {
				// Handle the case where 'sizes' is not set or not an array.
				$largest_image = null; // or any other default value or error handling.
				if ( OPENASSET_ENABLE_LOGGING ) {
					error_log( 'Invalid or missing image sizes in $image_data.' );
				}
			}

			if ( $largest_image ) {
				$full_image_url = 'https:' . $largest_image['http_root'] . $largest_image['http_relative_path'];
				// Now $full_image_url contains the URL of the largest image by area.
			} else {
				// Handle the case where $sizes is empty or does not contain any valid arrays.
				$full_image_url = null;
			}

			if ( $full_image_url ) {
				unset( $image_data['sizes'] );
				$this->add_single_image_to_media_library( $image_data['id'], $full_image_url, $post_id, false, $image_data );
			}
		}
	}

	/**
	 * Get the largest image size.
	 *
	 * @param array $sizes Image sizes.
	 * @return array
	 */
	public function get_largest_image( $sizes ) {
		// Get the largest image size url.
		$max_area      = 0;
		$largest_image = null;

		foreach ( $sizes as $size ) {
			$area = $size['width'] * $size['height'];
			if ( $area > $max_area ) {
				$max_area      = $area;
				$largest_image = $size;
			}
		}

		return $largest_image;
	}

	/**
	 * Add single image to media library - trigger as scheduled event when outputting images on the frontend.
	 *
	 * @param int    $image_id Image ID.
	 * @param string $image_url Image URL.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function add_single_image_to_media_library( $image_id, $image_url, $post_id, $hero_image = false, $meta_data = array() ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$existing_attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'meta_key'       => 'openasset_file_id',
				'meta_value'     => $image_id,
				'posts_per_page' => 1,
			)
		);

		// get postmeta 'md5_at_upload' if not equal to $meta_data['md5_at_upload'] then update the image.
		$md5_at_upload = isset( $meta_data['md5_at_upload'] ) ? $meta_data['md5_at_upload'] : '';

		$existing_md5_at_upload = false;

		if ( ! empty( $existing_attachments ) && isset( $existing_attachments[0] ) && isset( $existing_attachments[0]->ID ) ) {
			$existing_md5_at_upload = get_post_meta( $existing_attachments[0]->ID, 'md5_at_upload', true );
		}

		if ( ! empty( $existing_attachments ) && $md5_at_upload === $existing_md5_at_upload ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( "Image with external ID $image_id already exists." );
			}
			// Set attachment metadata.
			$caption               = isset( $meta_data['caption'] ) ? $meta_data['caption'] : '';
			$description           = isset( $meta_data['description'] ) ? $meta_data['description'] : '';
			$photographer          = isset( $meta_data['photographer'] ) ? $meta_data['photographer'] : '';
			$copyright_holder      = isset( $meta_data['copyright_holder'] ) ? $meta_data['copyright_holder'] : '';
			$created               = isset( $meta_data['created'] ) ? $meta_data['created'] : '';
			$updated               = isset( $meta_data['updated'] ) ? $meta_data['updated'] : '';
			$uploaded              = isset( $meta_data['uploaded'] ) ? $meta_data['uploaded'] : '';
			$rank                  = isset( $meta_data['rank'] ) ? $meta_data['rank'] : '';
			$project_display_order = isset( $meta_data['project_display_order'] ) ? $meta_data['project_display_order'] : '';

			$attachment = array(
				'ID'           => $existing_attachments[0]->ID,
				'post_excerpt' => $caption,       // Caption.
				'post_content' => $description,   // Description.
			);

			// Update the attachment metadata.
			wp_update_post( $attachment );

			// udpate custom meta fields.
			if ( ! empty( $photographer ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'photographer', $photographer );
			}
			if ( ! empty( $copyright_holder ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'copyright_holder', $copyright_holder );
			}
			if ( ! empty( $created ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'created', $created );
			}
			if ( ! empty( $updated ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'updated', $updated );
			}
			if ( ! empty( $uploaded ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'uploaded', $uploaded );
			}
			if ( ! empty( $rank ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'rank', $rank );
			}
			if ( ! empty( $project_display_order ) ) {
				update_post_meta( $existing_attachments[0]->ID, 'project_display_order', $project_display_order );
			}

			$existing_attachment_id = $existing_attachments[0]->ID;

			wp_update_post(
				array(
					'ID'          => $existing_attachment_id,
					'post_parent' => $post_id,
				)
			);

			if ( $hero_image ) {
				set_post_thumbnail( $post_id, $existing_attachment_id );
			}
			return;
		}

		// If md5_at_upload is not equal to existing md5_at_upload then it has been updated on openasset and must be replaced.
		if ( ! empty( $existing_attachments ) && $md5_at_upload !== $existing_md5_at_upload ) {
			// Delete the existing attachment.
			wp_delete_attachment( $existing_attachments[0]->ID, true );
		}

		$sideload_result = media_sideload_image( $image_url, $post_id, null, 'id' );

		// Check for errors.
		if ( is_wp_error( $sideload_result ) ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( "Failed to download image from: $image_url. Error: " . $sideload_result->get_error_message() );
			}
		} else {
			// Add the external file ID as post meta.
			add_post_meta( $sideload_result, 'openasset_file_id', $image_id, true );
			// Add md5_at_upload value to work out if the image was updated on OpenAsset side.
			$md5_at_upload = isset( $meta_data['md5_at_upload'] ) ? $meta_data['md5_at_upload'] : '';
			add_post_meta( $sideload_result, 'md5_at_upload', $md5_at_upload, true );
			// Remove the original source URL.
			delete_post_meta( $sideload_result, '_source_url' );

			// Set attachment metadata.
			$caption               = isset( $meta_data['caption'] ) ? $meta_data['caption'] : '';
			$description           = isset( $meta_data['description'] ) ? $meta_data['description'] : '';
			$photographer          = isset( $meta_data['photographer'] ) ? $meta_data['photographer'] : '';
			$copyright_holder      = isset( $meta_data['copyright_holder'] ) ? $meta_data['copyright_holder'] : '';
			$created               = isset( $meta_data['created'] ) ? $meta_data['created'] : '';
			$updated               = isset( $meta_data['updated'] ) ? $meta_data['updated'] : '';
			$uploaded              = isset( $meta_data['uploaded'] ) ? $meta_data['uploaded'] : '';
			$rank                  = isset( $meta_data['rank'] ) ? $meta_data['rank'] : '';
			$project_display_order = isset( $meta_data['project_display_order'] ) ? $meta_data['project_display_order'] : '';

			$attachment = array(
				'ID'           => $sideload_result,
				'post_excerpt' => $caption,       // Caption.
				'post_content' => $description,   // Description.
			);

			// Update the attachment metadata.
			wp_update_post( $attachment );

			// Add custom meta fields.
			if ( ! empty( $photographer ) ) {
				add_post_meta( $sideload_result, 'photographer', $photographer, true );
			}
			if ( ! empty( $copyright_holder ) ) {
				add_post_meta( $sideload_result, 'copyright_holder', $copyright_holder, true );
			}
			if ( ! empty( $created ) ) {
				add_post_meta( $sideload_result, 'created', $created, true );
			}
			if ( ! empty( $updated ) ) {
				add_post_meta( $sideload_result, 'updated', $updated, true );
			}
			if ( ! empty( $uploaded ) ) {
				add_post_meta( $sideload_result, 'uploaded', $uploaded, true );
			}
			if ( ! empty( $rank ) ) {
				add_post_meta( $sideload_result, 'rank', $rank, true );
			}
			if ( ! empty( $project_display_order ) ) {
				add_post_meta( $sideload_result, 'project_display_order', $project_display_order, true );
			}

			if ( $hero_image ) {
				set_post_thumbnail( $post_id, $sideload_result );
			}
		}
	}

	/**
	 * Add sort value to post when settings are updated.
	 *
	 * @param array $sort_by Sort value.
	 *
	 * @return bool
	 */
	public function add_sort_value_to_post( $sort_by ) {
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Adding sort value to posts.' );
		}

		$data     = get_option( self::OPTION_DATA_KEY );
		$settings = get_option( self::OPTION_SETTINGS_KEY );

		$post_types = array( 'employee', 'project' );

		foreach ( $post_types as $post_type ) {
			$fields = $data['fields'][ $post_type ];

			if ( ! isset( $settings['data-options'][ $post_type . '-sort-by' ] ) ) {
				if ( OPENASSET_ENABLE_LOGGING ) {
					error_log( "Adding sort value to $post_type posts exited before updating as sort by is not set." );
				}
				continue;
			}

			$field_id = (int) $sort_by[ $post_type ];

			$sort_by_rest_code_name = '';

			foreach ( $fields as $field ) {
				if ( $field_id === $field['id'] ) {
					$sort_by_rest_code_name = $field['rest_code'];
					break;
				}
			}

			if ( '' === $sort_by_rest_code_name ) {
				$sort_by_rest_code_name = 'code';
			}

			$all_posts = get_posts(
				array(
					'post_type'      => 'oa-' . $post_type,
					'posts_per_page' => -1,
				)
			);

			foreach ( $all_posts as $post ) {
				$openasset_data = get_post_meta( $post->ID, 'openasset_data', true );

				if ( 'employee' === $post_type ) {
					$sort_value = isset( $openasset_data[ $sort_by_rest_code_name ] ) ? $openasset_data[ $sort_by_rest_code_name ] : '';
				} else {
					if ( 'name' === $sort_by_rest_code_name ) {
						$sort_value = $openasset_data['name'];
					} elseif ( 'code' === $sort_by_rest_code_name ) {
						$sort_value = $openasset_data['code'];
					} else {
						foreach ( $openasset_data['fields'] as $field ) {
							if ( $field_id === $field['id'] ) {
								$sort_value = $field['values'][0];
								break;
							}
						}
					}
				}

				update_post_meta( $post->ID, 'sort_value', $sort_value );
			}
		}
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Adding sort value to posts completed.' );
		}
		// To be updated.
		return true;
	}

	/**
	 * Add keywords to the post when it is created.
	 *
	 * @param string $post_id    The post ID.
	 * @param array  $terms_data All parent child keywords from openasset.
	 *
	 * @return void
	 */
	public function sync_taxonomy_terms( $post_id, $terms_data ) {
		$taxonomy = 'keyword';  // your custom taxonomy slug.

		$new_term_ids = array();

		foreach ( $terms_data as $term_data ) {
			$parent_term_name = $term_data['parent'];
			$child_term_name  = $term_data['child'];

			// Ensure parent term exists.
			$parent_term = term_exists( $parent_term_name, $taxonomy );
			if ( ! $parent_term ) {
				$parent_term = wp_insert_term( $parent_term_name, $taxonomy );
			}
			$parent_term_id = is_array( $parent_term ) ? $parent_term['term_id'] : $parent_term;

			// Ensure child term exists.
			$child_term = term_exists( $child_term_name, $taxonomy );
			if ( ! $child_term ) {
				$child_term = wp_insert_term( $child_term_name, $taxonomy, array( 'parent' => $parent_term_id ) );
			}
			$child_term_id = is_array( $child_term ) ? $child_term['term_id'] : $child_term;

			// Add to new terms list.
			$new_term_ids[] = $parent_term_id;
			$new_term_ids[] = $child_term_id;
		}

		// Set post terms (this will remove terms not in $new_term_ids and add those that are).
		wp_set_object_terms( $post_id, $new_term_ids, $taxonomy, false );
	}

}
