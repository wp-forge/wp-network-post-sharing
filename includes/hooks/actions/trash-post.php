<?php

add_action( 'wp_trash_post', function ( $post_id ) {
    if ( in_array( get_post_type( $post_id ), wpfnps_post_types() ) ) {
        $shared_with = wpfnps_get_shares( $post_id );

        foreach ( $shared_with as $site_id ) {
            $remote_id = get_post_meta( $post_id, "_remote_share_id_{$site_id}", 'single' );

            if ( $remote_id ) {
                switch_to_blog( $site_id );
                wp_delete_post( $remote_id, "force delete" );
                restore_current_blog();
            }
        }
    }
} );
