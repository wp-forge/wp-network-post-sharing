<?php
add_action( 'before_delete_post', function ( $post_id ) {

    if ( in_array( get_post_type( $post_id ), wpfnps_post_types() ) ) {
       wpfnps_delete_remote_shares( $post_id );
       wpfnps_delete_origin_share_reference( $post_id );
    }
} );

