<?php
/**
 * Template part for displaying posts
 *
 * @package OpenAsset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
use OpenAsset\Core\Helpers;

$helpers = new Helpers();

$oa_data             = get_post_meta( $post->ID, 'openasset_data', true );
$oa_data_options     = get_option( 'openasset_data' );
$oa_settings_options = get_option( 'openasset_settings' );

$employees = isset( $oa_data['employees'] ) ? $oa_data['employees'] : array(); // array of arrays.
$criteria  = isset( $oa_settings_options['data-options']['project-criteria-fields'] ) ? $oa_settings_options['data-options']['project-criteria-fields'] : array(); // array of ids except 0.
if ( ! is_array( $criteria ) ) {
	$criteria = array();
}


$keyword_categories = isset( $oa_settings_options['data-options']['project-criteria-keyword-categories'] ) ? $oa_settings_options['data-options']['project-criteria-keyword-categories'] : array(); // array of ids except 0.

$fields          = $oa_data_options['fields']['project']; // array of all field details.
$filtered_fields = array();

foreach ( $fields as $field ) {
	if ( isset( $field['rest_code'] ) && isset( $field['id'] ) ) {
		$filtered_fields[ $field['rest_code'] ] = $field['id'];
	}
}
$project_fields = array();
foreach ( $oa_data['fields'] as $field ) {
	if ( isset( $field['id'] ) && isset( $field['values'] ) ) {
		$project_fields[ $field['id'] ] = $field['values']['0'];
	}
}

$project_post_type_obj = get_post_type_object( 'oa-project' );

if ( ! empty( $project_post_type_obj ) ) {
	// Get the plural label of the post type.
	$project_plural_name = $project_post_type_obj->labels->name;
} else {
	$project_plural_name = 'Projects';
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="oa-hero-image container">
		<?php
		$hero_image_url = get_the_post_thumbnail_url( $post, 'full', '' );
		if ( ! $hero_image_url ) {
			$hero_image_url = OPENASSET_URL . '/assets/img/project-placeholder.jpg';
		}
		?>
		<img src="<?php echo esc_url( $hero_image_url ); ?>" alt="<?php echo esc_html( get_the_title() ); ?>">
	</div>
	<div class="container">
		<div class="oa-container project-template">
			<div class="row justify-content-between">
				<div class="col-12 col-md-8 padding-right-40">
					<div class="oa-title">
						<!-- Name --> 
						<?php if ( in_array( $filtered_fields['name'], $criteria, true ) ) : ?>
							<h1><?php the_title(); ?></h1>
						<?php endif; ?>
					</div>
					<div class="oa-description">
						<!-- Description -->
						<?php if ( in_array( $filtered_fields['project_description'], $criteria, true ) ) : ?>
							<?php $description = $project_fields[ $filtered_fields['project_description'] ]; ?>
							<?php echo nl2br( esc_html( $description ) ); ?>
						<?php endif; ?>
					</div>
					<div class="oa-gallery">
						<?php
						// Project images.
						$featured_image_id = get_post_thumbnail_id( $post->ID ); // Get the featured image ID.

						$args = array(
							'post_type'      => 'attachment',
							'post_mime_type' => 'image',
							'post_status'    => 'inherit',
							'posts_per_page' => $oa_settings_options['data-options']['maximum-images-per-project'], // Adjust as needed.
							'post_parent'    => $post->ID,
							'exclude'        => $featured_image_id, // Exclude the featured image.
							'meta_key'       => $oa_settings_options['data-options']['project-images-sort-by'], // Key of the custom field to sort by.
							'orderby'        => 'meta_value', // Tells WP to sort by the value of the meta_key.
							'order'          => $oa_settings_options['data-options']['project-images-order-by'], // Sort in ascending order.
						);

						$attached_images = get_posts( $args );

						if ( $attached_images ) {
							foreach ( $attached_images as $attachment ) {
								$image_url = wp_get_attachment_image_src( $attachment->ID, 'full' ); // Change 'full' to desired image size.

								$image_description = '';

								// Get attachment metadata.
								$caption = get_post_field( 'post_excerpt', $attachment->ID );

								// Append details to image description.
								if ( ! empty( $caption ) ) {
									$image_description .= $caption;
								}
								?>
								<div class="oa-gallery-image">
								<a class="oa-gallery-image-link" href="<?php echo esc_url( $image_url[0] ); ?>" data-lightbox="oa-gallery-image" data-title="<?php echo esc_html( $image_description ); ?>">
									<img src="<?php echo esc_url( $image_url[0] ); ?>" alt="<?php echo esc_html( $image_description ); ?>">
								</a>
							</div>
								<?php
							}
						}
						?>
					</div>

				</div>
				<div class="col-12 col-md-4 padding-left-40">
					<div class="oa-details-wide"></div>
					<div class="oa-details">
						<?php
						$skip_fields = array( 'show_on_website', 'name', 'project_description' );
						foreach ( $fields as $field ) {
							if ( ! in_array( $field['id'], $criteria, true ) ) {
								continue;
							}
							if ( in_array( $field['rest_code'], $skip_fields, true ) ) {
								continue;
							}
							$field_name  = $field['name'];
							$field_value = isset( $project_fields[ $field['id'] ] ) ? $project_fields[ $field['id'] ] : '';
							if ( 'date' === $field['field_display_type'] ) {
								$field_value = $helpers->convert_to_readable_date( $field_value );
							}
							if ( '' === $field_value || null === $field_value ) {
								continue;
							}
							?>
							<div class="oa-detail-pair">
								<p><?php echo esc_html( $field_name ); ?></p>
								<p><?php echo esc_html( $field_value ); ?></p>
							</div>
							<?php
						}
						?>
						<?php
						// Fetch all the 'keyword' taxonomy terms associated with the current post.
						$keywords = get_the_terms( $post->ID, 'keyword' );
						if ( ! empty( $keywords ) && ! empty( $keyword_categories ) ) :
							?>
						<div class="oa-detail-pair">
							<p><?php echo esc_html__( 'Keywords', 'openasset' ); ?></p>
							<div class="keyword-list">
							<?php
							// Check if there are any keywords.
							if ( $keywords && ! is_wp_error( $keywords ) ) {

								// Create an array to store the formatted keyword links.
								$keyword_links = array();

								// Generate a nonce.
								$nonce = wp_create_nonce( 'keyword_filters_nonce' );

								// Loop through each keyword.
								foreach ( $keywords as $keyword ) {
									$parent_id = $keyword->parent;
									$parent_open_asset_id = get_term_meta( $parent_id, 'openasset_id', true );
									if ( ! in_array( (int) $parent_open_asset_id, $keyword_categories, true ) ) {
										continue;
									}

									// Get the URL of the archive page for the keyword.
									$keyword_url = get_term_link( $keyword->term_id );

									// Check if there was an error generating the URL.
									if ( ! is_wp_error( $keyword_url ) ) {

										// Construct the URL to the project archive page filtered by the keyword.
										$keyword_url = get_post_type_archive_link( 'oa-project' ) . '?keywordfilters%5B%5D=' . rawurlencode( $keyword->slug ) . '&keyword_filters_nonce=' . $nonce;

										// Create a clickable link and add it to the array.
										$keyword_links[] = '<a href="' . esc_url( $keyword_url ) . '">' . esc_html( $keyword->name ) . '</a>';
									}
								}

								// Combine all keyword links into a string separated by a forward slash.
								$keywords_string = implode( ' / ', $keyword_links );

								// Allowed HTML tags and attributes for links.
								$allowed_html = array(
									'a' => array(
										'href'  => array(),
										'title' => array(),
									),
								);


								// Output the keywords safely.
								echo wp_kses( $keywords_string, $allowed_html );
							}
							?>
							</div>
						</div>
						<?php endif; ?>
					</div>
					<?php
					if ( ! empty( $employees ) ) :

						$team_members_html = ''; // Initialize an empty string to hold team members HTML.

						foreach ( $employees as $employee_data ) {
							$employee_data_id = $employee_data['id'];

							$args = array(
								'post_type'      => 'oa-employee',
								'meta_key'       => 'openasset_id',
								'meta_value'     => $employee_data_id,
								'posts_per_page' => 1,
							);

							$wp_post = get_posts( $args );

							if ( ! empty( $wp_post ) ) {
								$employee_post_id = $wp_post[0]->ID;

								// Get the 'openasset_data' post meta.
								$openasset_data = get_post_meta( $employee_post_id, 'openasset_data', true );

								$first_name    = $openasset_data['first_name'];
								$last_name     = $openasset_data['last_name'];
								$employee_name = $first_name . ' ' . $last_name;

								$employee_url = get_permalink( $employee_post_id );

								$thumbnail_id = get_post_thumbnail_id( $employee_post_id );

								// Get the image URL.
								$image_url = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );

								if ( $image_url ) {
									$employee_image = $image_url[0];
								} else {
									$employee_image = OPENASSET_URL . '/assets/img/employee-placeholder.jpg';
								}

								// Build the HTML for this team member.
								$team_member_html = '<div class="oa-team-member">
									<div class="oa-team-member-image">
										<figure class="post-thumbnail">
											<a href="' . esc_url( $employee_url ) . '">
												<img src="' . esc_url( $employee_image ) . '" alt="' . esc_attr( $employee_name ) . '">
											</a>
										</figure>
									</div>
									<div class="oa-team-member-details">
										<a href="' . esc_url( $employee_url ) . '">
											<p>' . esc_html( $employee_name ) . '</p>
										</a>
										<p>' . esc_html( $employee_data['roles']['rows'][0]['project_role'] ) . '</p>
									</div>
								</div>';

								// Append to the team members HTML.
								$team_members_html .= $team_member_html;
							}

							wp_reset_postdata();
						}

						// Only output the team section if there are team members.
						if ( ! empty( $team_members_html ) ) :
							?>
							<div class="oa-team">
								<h2><?php echo esc_html__( 'Meet the team', 'openasset' ); ?></h2>
								<div class="oa-team-container">
									<?php echo $team_members_html; ?>
								</div>
							</div>
							<?php
						endif;

					endif;
					?>

				</div>
			</div>

			<div class="oa-footer">
				<!-- < back to all projects -->
				<a href="<?php echo esc_url( get_post_type_archive_link( 'oa-project' ) ); ?>">
					<button class="btn oa-btn">
						<?php
						printf(
							/* translators: %s: Projects custom plural name  */
							esc_html__( '< Back to All %s', 'openasset' ),
							esc_html( ucfirst( $project_plural_name ) ),
						);
						?>
					</button>
				</a>
			</div>
		</div>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->
