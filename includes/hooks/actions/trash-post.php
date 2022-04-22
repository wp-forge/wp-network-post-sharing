<?php

if ( function_exists( 'get_field' ) ) {
    add_action( 'wp_trash_post', function ( $post_id ) {
        if ( 'cirrus-aircraft' === get_post_type( $post_id ) ) {
            $shared_with = get_field( 'share_aircraft', $post_id );

            foreach ( $shared_with as $site_id ) {
                $remote_id = get_post_meta( $post_id, "_remote_share_id_{$site_id}", 'single' );

                if ( $remote_id ) {
                    switch_to_blog( $site_id );
                    wp_delete_post( $remote_id, "force delete" );
                    restore_current_blog();
                }
            }
        }
    });
}
