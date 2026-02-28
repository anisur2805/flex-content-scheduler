<?php

namespace Anisur\ContentScheduler\Helpers;

class PostTypeHelper {
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
