<?php
/**
 * Post type helper utilities.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Helpers;

/**
 * Class PostTypeHelper
 *
 * Provides helper methods for retrieving public post type information.
 *
 * @since 1.0.0
 */
class PostTypeHelper {
	/**
	 * Get all public post types as an array of slug/label pairs.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of arrays with 'slug' and 'label' keys.
	 */
	public static function get_public_post_types(): array {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		$out   = array();

		foreach ( $types as $type ) {
			$out[] = array(
				'slug'  => sanitize_key( $type->name ),
				'label' => sanitize_text_field( $type->label ),
			);
		}

		return $out;
	}
}
