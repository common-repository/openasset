<?php
/**
 * ApiHelpers class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ApiHelpers
 */
class ApiHelpers {

	/**
	 * Options key.
	 */
	const OPTION_SETTINGS_KEY = 'openasset_settings';
	const OPTION_DATA_KEY     = 'openasset_data';

	/**
	 * Generate Request URL
	 *
	 * @param string $post_type Post type to get.
	 *
	 * @return string
	 */
	public function generate_request_url( $post_type ) {
		if ( 'employee' === $post_type ) {
			return $this->generate_employee_request_url();
		} elseif ( 'project' === $post_type ) {
			return $this->generate_project_request_url();
		} else {
			return '';
		}
	}

	/**
	 * Generate employee request.
	 *
	 * @return string
	 */
	public function generate_employee_request_url() {
		$limit = 250;
		$url   = "/Employees?alive=1&deleted=0&limit={$limit}&show_on_website=1&withHeroImage=1&projects[roles][limit]=15";

		$settings = get_option( self::OPTION_SETTINGS_KEY );
		$data     = get_option( self::OPTION_DATA_KEY );

		$display_fields_ids = isset( $settings['data-options']['employee-criteria-fields'] ) ? $settings['data-options']['employee-criteria-fields'] : array();

		$display_fields = array( 'id', 'hero_image_id' );

		foreach ( $data['fields']['employee'] as $field ) {
			if ( in_array( $field['id'], $display_fields_ids, true ) ) {
				$display_fields[] = $field['rest_code'];
			}
		}

		$display_fields = implode( ',', $display_fields );

		$url .= '&displayFields=' . $display_fields;

		$sort_by_id = isset( $settings['data-options']['employee-sort-by'] ) ? $settings['data-options']['employee-sort-by'] : '';

		$sort_by = 'last_name';

		foreach ( $data['fields']['employee'] as $field ) {
			if ( $field['id'] == $sort_by_id ) {
				$sort_by = $field['rest_code'];
			}
		}

		$order_by = isset( $settings['data-options']['employee-order-by'] ) ? $settings['data-options']['employee-order-by'] : 'Desc';

		$url .= '&orderBy=' . $sort_by . $order_by;

		return $url;
	}

	/**
	 * Generate project request url.
	 *
	 * @return string
	 */
	public function generate_project_request_url() {
		$limit = 250;
		$url   = "/Projects?alive=1&deleted=0&limit={$limit}&show_on_website=1&withHeroImage=1";

		$settings = get_option( self::OPTION_SETTINGS_KEY );
		$data     = get_option( self::OPTION_DATA_KEY );

		$display_fields_ids = isset( $settings['data-options']['project-criteria-fields'] ) ? $settings['data-options']['project-criteria-fields'] : array();

		// built_in fields which uses the field rest_code.
		$display_fields = array( 'id', 'hero_image_id' );

		foreach ( $data['fields']['project'] as $field ) {
			if ( in_array( $field['id'], $display_fields_ids, true ) && 1 === $field['built_in'] ) {
				$display_fields[] = $field['rest_code'];
			}
		}

		$display_fields = implode( ',', $display_fields );

		$url .= '&displayFields=' . $display_fields;

		// Non built_in fields which uses the field id, not rest_code.
		$fields = array();

		foreach ( $data['fields']['project'] as $field ) {
			if ( in_array( $field['id'], $display_fields_ids, true ) && 0 === $field['built_in'] ) {
				$fields[] = $field['id'];
			}
		}

		$fields = implode( ',', $fields );

		$url .= '&fields=' . $fields;

		// Include the employees limits.
		$url .= '&employees[limit]=100&employees[roles][limit]=100';

		$employee_role_display_fields = array();

		$employee_role_display_field_ids = isset( $settings['data-options']['roles-criteria-fields'] ) ? $settings['data-options']['roles-criteria-fields'] : array();

		foreach ( $data['grid-columns'] as $grid_column ) {
			if ( in_array( $grid_column['id'], $employee_role_display_field_ids, true ) ) {
				$employee_role_display_fields[] = $grid_column['code'];
			}
		}

		$employees_roles_display_fields = implode( ',', $employee_role_display_fields );

		$url .= '&employees[roles][displayFields]=' . $employees_roles_display_fields;

		// Include all keywords.
		$url .= '&projectKeywords[limit]=0';

		$sort_by_id = isset( $settings['data-options']['project-sort-by'] ) ? $settings['data-options']['project-sort-by'] : '';

		$sort_by = 'name';

		foreach ( $data['fields']['project'] as $field ) {
			if ( $field['id'] == $sort_by_id ) {
				$sort_by = $field['rest_code'];
			}
		}

		$order_by = isset( $settings['data-options']['project-order-by'] ) ? $settings['data-options']['project-order-by'] : 'Desc';

		$url .= '&orderBy=' . $sort_by . $order_by;

		return $url;
	}

}
