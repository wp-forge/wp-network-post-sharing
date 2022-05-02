<?php

add_action( 'add_meta_boxes', function () {

	$post_types = apply_filters( 'wpforge_network_post_sharing_post_types', array() );

	if ( in_array( get_post_type(), $post_types ) ) {
		add_meta_box(
			'wpforge_network_post_sharing',
			__( 'Network Post Sharing Details', 'wpforge_network_post_sharing' ),
			'wp_network_post_sharing_meta_box'
		);
	}

} );

function wp_network_post_sharing_meta_box() {

	printf( '<label for="wpfnps-share-with">%1$s</label><br />', __( 'Share with other sites?', 'wpforge_network_post_sharing' ) );
	print '<select id="wpfnps-share-with" class="" name="wpfnps_share_with[]" multiple="multiple" size="5" class="widefat">';

	$sites   = get_sites();
	$current = get_current_blog_id();
	$shares  = wpfnps_share_with();

	printf( '<option value="0" %2$s>%1$s</option>', __( 'None', 'wpforge_network_post_sharing' ), empty( $shares ) || 0 === $shares[0] ? 'selected="selected"' : '' );

	foreach ( $sites as $site ) {
		/**
		 * @var WP_Site $site
		 */
		if ( $current != $site->blog_id ) {
			printf( '<option value="%1$s" %3$s>%2$s</option>', esc_attr( $site->blog_id ), wp_kses_post( $site->blogname ), in_array( $site->blog_id, $shares ) ? 'selected="selected"' : '' );
		}
	}

	print '</select>';
}

