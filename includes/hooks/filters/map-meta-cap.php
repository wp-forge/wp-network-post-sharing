<?php
/**
 * The map_meta_cap filter callback
 *
 * @link https://developer.wordpress.org/reference/hooks/map_meta_cap/
 */

add_filter(
	'map_meta_cap',
	function ( $caps, $cap, $user_id, $args ) {

		if ( 'edit_post' === $cap && isset( $args[0] ) ) {

			$post_id = $args[0];

			if ( get_post_meta( $post_id, '_origin_blog_id' ) ) {
				// This is a shared listing and therefore is not editable
				$caps[] = 'do_not_allow';
			}

		}

		return $caps;

	},
	10,
	4
);
