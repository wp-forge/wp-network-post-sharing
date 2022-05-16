<?php
/**
 * @param int     $post_ID
 * @param WP_Post $post_after
 * @param WP_Post $post_before
 */
add_action( 'post_updated', function( $post_ID, $post_after ) {

    $shares = get_post_meta( $post_ID, '_wpforge_network_post_sharing_share_with', 'single' );

    if ( $shares ) {
        foreach( $shares as $share ) {
            $remote_share_id = get_post_meta( $post_ID, "_remote_share_id_{$share}" , 'single' );

            if( $remote_share_id ) {
                $post_after->ID = $remote_share_id;
                switch_to_blog( $share );
                wp_update_post( $post_after );
                restore_current_blog();
            }
        }
    }
}, 10, 2 );