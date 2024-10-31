<?php
/**
 * Template part for displaying single employee content
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

$projects = isset( $oa_data['projects'] ) ? $oa_data['projects'] : array(); // array of arrays.
$criteria = isset( $oa_settings_options['data-options']['employee-criteria-fields'] ) ? $oa_settings_options['data-options']['employee-criteria-fields'] : array(); // array of ids except 0.
if ( ! is_array( $criteria ) ) {
	$criteria = array();
}
$roles_criteria = isset( $oa_settings_options['data-options']['roles-criteria-fields'] ) ? $oa_settings_options['data-options']['roles-criteria-fields'] : array(); // array of ids except 0.
if ( ! is_array( $roles_criteria ) ) {
	$roles_criteria = array();
}

$filtered_roles_criteria = array();
foreach ( $oa_data_options['grid-columns'] as $column ) {
	if ( isset( $column['code'] ) && isset( $column['id'] ) ) {
		$filtered_roles_criteria[ $column['code'] ] = $column['id'];
	}
}

$fields          = isset( $oa_data_options['fields']['employee'] ) ? $oa_data_options['fields']['employee'] : array(); // array of all field details.
$filtered_fields = array();

foreach ( $fields as $field ) {
	if ( isset( $field['rest_code'] ) && isset( $field['id'] ) ) {
		$filtered_fields[ $field['rest_code'] ] = $field['id'];
	}
}

$employee_post_type_obj = get_post_type_object( 'oa-employee' );

if ( ! empty( $employee_post_type_obj ) ) {
	// Get the plural label of the post type.
	$employee_plural_name = $employee_post_type_obj->labels->name;
} else {
	$employee_plural_name = 'Team';
}

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="container">
		<div class="oa-container employee-template">
			<div class="row justify-content-between">
				<div class="col-12 col-md-4 padding-right-40">
					<div class="oa-profile-image">
						<?php
						$hero_image_url = get_the_post_thumbnail_url( $post, 'large', '' );
						if ( ! $hero_image_url ) {
							$hero_image_url = OPENASSET_URL . '/assets/img/employee-placeholder.jpg';
						}
						?>
						<img src="<?php echo esc_url( $hero_image_url ); ?>" alt="<?php echo esc_html( get_the_title() ); ?>">
					</div>
					<?php
					if ( ! empty( $projects ) ) :

						$projects_html = ''; // Initialize an empty string to hold projects HTML.

						foreach ( $projects as $project_data ) {
							$project_data_id = $project_data['id'];

							$args = array(
								'post_type'      => 'oa-project',
								'meta_key'       => 'openasset_id',
								'meta_value'     => $project_data_id,
								'posts_per_page' => 1,
							);

							$wp_post = get_posts( $args );

							if ( ! empty( $wp_post ) ) {
								$employee_post_id = $wp_post[0]->ID;

								// Get the 'openasset_data' post meta.
								$openasset_data = get_post_meta( $employee_post_id, 'openasset_data', true );

								$project_name = $openasset_data['name'];

								$project_url = get_permalink( $employee_post_id );

								$thumbnail_id = get_post_thumbnail_id( $employee_post_id );

								// Get the image URL.
								$image_url = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );

								if ( $image_url ) {
									$project_image = $image_url[0];
								} else {
									$project_image = OPENASSET_URL . '/assets/img/project-placeholder.jpg';
								}

								// Build the HTML for this project
								$project_html = '<div class="oa-project">
									<div class="oa-project-image">
										<figure class="post-thumbnail">
											<a href="' . esc_url( $project_url ) . '">
												<img src="' . esc_url( $project_image ) . '" alt="' . esc_attr( $project_name ) . '">
											</a>
										</figure>
									</div>
									<div class="oa-project-details">
										<a href="' . esc_url( $project_url ) . '">
											<p>' . esc_html( $project_name ) . '</p>
										</a>';

								// Display the project's role in the project
								$employee_role = $project_data['roles']['rows'][0]['project_role'];
								if ( ( '' !== $employee_role ) && in_array( $filtered_roles_criteria['project_role'], $roles_criteria, true ) ) {
									$project_html .= '<p>Role: ' . esc_html( $employee_role ) . '</p>';
								}

								// Display hours worked.
								$hours_worked = $project_data['roles']['rows'][0]['hours'];
								if ( ( '' !== $hours_worked ) && in_array( $filtered_roles_criteria['hours'], $roles_criteria, true ) ) {
									$project_html .= '<p>Hours Worked: ' . esc_html( $hours_worked ) . '</p>';
								}

								// Display start date.
								$start_date = $project_data['roles']['rows'][0]['start_date'];
								if ( ( '' !== $start_date ) && in_array( $filtered_roles_criteria['start_date'], $roles_criteria, true ) ) {
									$start_date_readable = $helpers->convert_to_readable_date( $start_date );
									$project_html .= '<p>Start Date: ' . esc_html( $start_date_readable ) . '</p>';
								}

								// Display end date
								$end_date = $project_data['roles']['rows'][0]['end_date'];
								if ( ( '' !== $end_date ) && in_array( $filtered_roles_criteria['end_date'], $roles_criteria, true ) ) {
									$end_date_readable = $helpers->convert_to_readable_date( $end_date );
									$project_html .= '<p>End Date: ' . esc_html( $end_date_readable ) . '</p>';
								}

								// Display role description.
								$description = $project_data['roles']['rows'][0]['role_description'];
								if ( ( '' !== $description ) && in_array( $filtered_roles_criteria['role_description'], $roles_criteria, true ) ) {
									$project_html .= '<p>' . esc_html( $description ) . '</p>';
								}

								$project_html .= '</div></div>';

								// Append to the projects HTML.
								$projects_html .= $project_html;
							}

							wp_reset_postdata();
						}

						// Only output the projects section if there are projects.
						if ( ! empty( $projects_html ) ) :
							?>
							<div class="oa-team-member-projects">
								<h2 class="oa-team-member-projects-heading"><?php echo esc_html__( 'Projects', 'openasset' ); ?></h2>
								<div class="oa-projects-container">
									<?php echo $projects_html; ?>
								</div>
							</div>
							<?php
						endif;

					endif;
					?>
				</div>
				<div class="col-12 col-md-8 padding-left-40">
					<div class="oa-title">
						<!-- Name -->
						<?php if ( in_array( $filtered_fields['first_name'], $criteria, true ) || in_array( $filtered_fields['last_name'], $criteria, true ) ) : ?>
							<?php
							$name = '';
							if ( in_array( $filtered_fields['first_name'], $criteria, true ) ) {
								$name .= $oa_data['first_name'];
							}
							if ( in_array( $filtered_fields['last_name'], $criteria, true ) ) {
								$name .= ' ' . $oa_data['last_name'];
							}
							?>
							<h1><?php echo esc_html( $name ); ?></h1>
						<?php endif; ?>
					</div>
					<div class="oa-job-title">
						<!-- Description -->
						<?php if ( in_array( $filtered_fields['job_title'], $criteria, true ) ) : ?>
							<?php $job_title = $oa_data['job_title']; ?>
							<p class=''><?php echo nl2br( esc_html( $job_title ) ); ?></p>
						<?php endif; ?>
					</div>
					<div class="oa-description">
						<!-- Description -->
						<?php if ( in_array( $filtered_fields['biography'], $criteria, true ) ) : ?>
							<?php $description = $oa_data['biography']; ?>
							<?php echo nl2br( esc_html( $description ) ); ?>
						<?php endif; ?>
					</div>
					<div class="oa-details">
						<?php
						$skip_fields = array( 'show_on_website', 'first_name', 'last_name', 'description', 'job_title', 'biography' );

						// sort criteria fields by id.
						foreach ( $fields as $field ) {
							if ( ! in_array( $field['id'], $criteria, true ) ) {
								continue;
							}
							if ( in_array( $field['rest_code'], $skip_fields, true ) ) {
								continue;
							}
							$field_name  = $field['name'];
							$rest_code   = $field['rest_code'];
							$field_value = isset( $oa_data[ $rest_code ] ) ? $oa_data[ $rest_code ] : '';
							if ( 'date' === $field['field_display_type'] ) {
								$field_value = $helpers->convert_to_readable_date( $field_value );
							}
							if ( '' === $field_value || null === $field_value ) {
								continue;
							}
							?>
							<div class="oa-detail-pair">
								<p class='text-uppercase smaller-text'><?php echo esc_html( $field_name ); ?></p>
								<p><?php echo esc_html( $field_value ); ?></p>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
			<div class="oa-footer">
				<!-- < back to all projects -->
				<a href="<?php echo esc_url( get_post_type_archive_link( 'oa-employee' ) ); ?>">
					<button class="btn oa-btn">
						<?php
						printf(
							/* translators: %s: Employee custom plural name  */
							esc_html__( '< Back to All %s', 'openasset' ),
							esc_html( ucfirst( $employee_plural_name ) ),
						);
						?>
					</button>
				</a>
			</div>
		</div>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->
