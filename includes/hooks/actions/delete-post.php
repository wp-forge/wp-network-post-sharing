<?php
add_action( 'before_delete_post', 'wpfnps_before_delete_post' );

function wpfnps_before_delete_post( $post_id ) {

	if ( in_array( get_post_type( $post_id ), wpfnps_post_types() ) ) {
		remove_action( 'before_delete_post', 'wpfnps_before_delete_post' );
		wpfnps_delete_remote_shares( $post_id );
		wpfnps_delete_origin_share_reference( $post_id );
		add_action( 'before_delete_post', 'wpfnps_before_delete_post' );
	}

}

