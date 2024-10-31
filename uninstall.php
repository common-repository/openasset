<?php
/**
 * Trigger this file on plugin uninstall.
 *
 * @package fuelmii-calculator
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Get all 'oa-project' posts.
$projects = get_posts(
	array(
		'post_type'   => 'oa-project',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $projects as $project_id ) {
	// Get all attachments for the project.
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_parent'    => $project_id,
			'fields'         => 'ids',
		)
	);

	// Delete attachments.
	foreach ( $attachments as $attachment_id ) {
		wp_delete_attachment( $attachment_id, true );
	}

	// Delete the project post.
	wp_delete_post( $project_id, true );
}

// Get all 'oa-employee' posts.
$employees = get_posts(
	array(
		'post_type'   => 'oa-employee',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $employees as $employee_id ) {
	// Get all attachments for the employee.
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_parent'    => $employee_id,
			'fields'         => 'ids',
		)
	);

	// Delete attachments.
	foreach ( $attachments as $attachment_id ) {
		wp_delete_attachment( $attachment_id, true );
	}

	// Delete the empoyee post.
	wp_delete_post( $employee_id, true );
}

// Remove all keywords.

// temporary register the taxonomy.
register_taxonomy( 'keyword', array( 'oa-project' ) );

// Get all terms in the custom taxonomy.
$terms = get_terms(
	array(
		'taxonomy'   => 'keyword',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		// Delete the term.
		wp_delete_term( $term_id, 'keyword' );
	}
}

// Clean up any scheduled events.
$timestamp = wp_next_scheduled( 'openasset_feed_refresh' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'openasset_feed_refresh' );
}

// Clean up any scheduled events.
$timestamp = wp_next_scheduled( 'run_openasset_sync' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'run_openasset_sync' );
}


delete_option( 'openasset_settings' );
delete_option( 'openasset_data' );
delete_option( 'openasset_sync_running' );
delete_option( 'openasset_sync_error_count' );
delete_option( 'openasset_last_employee_updated' );
delete_option( 'openasset_last_project_updated' );
delete_option( 'openasset_employee_posts_deleted' );
delete_option( 'openasset_project_posts_deleted' );
delete_option( 'openasset_total_employee_count' );
delete_option( 'openasset_total_project_count' );
