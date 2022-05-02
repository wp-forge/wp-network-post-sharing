<?php
/**
 * @var WP_Post $post
 * @var string[] $states
 */
add_filter( 'display_post_states', function ( $states, $post ) {

	$maybe = get_post_meta( $post->ID, '_origin_blog_id', 'single' );

	if ( $maybe ) {
		$states['shared'] = __( 'Shared', 'cirrus' );
	}

	return $states;

}, 10, 2 );
