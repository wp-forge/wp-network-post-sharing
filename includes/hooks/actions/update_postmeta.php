<?php

add_action( 'update_postmeta', 'wpnfps_update_postmeta', 10, 4 );

/**
 * @param  int    $meta_id
 * @param  int    $object_id
 * @param  string $meta_key
 * @param  string $meta_value
 * @return void
 */
function wpnfps_update_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {

    do {
        $current_shares = wpfnps_get_shares( $object_id );

        if ( '_wpforge_network_post_sharing_share_with' === $meta_key ) {
            $share_with = maybe_unserialize( $meta_value );

            if ( is_array( $share_with ) ) {
                if ( $share_with ) {
                    foreach( $share_with as $blog_id ) {
                        if( $blog_id ) {
                            $remote_id = wpfnps_update_post_share( $blog_id, $object_id );

                            if ( $remote_id ) {
                                update_post_meta( $object_id, "_remote_share_id_{$blog_id}", $remote_id );
                            }
                        }
                    }

                    $delete_from = array_diff( $current_shares, $share_with );

                    foreach( $delete_from as  $maybe_delete ) {
                        if ( $maybe_delete && ! in_array( $maybe_delete, $share_with ) ) {
                            wpfnps_delete_remote_share( $maybe_delete, $object_id );
                            delete_post_meta( $object_id, "_remote_share_id_{$maybe_delete}" );
                        }
                    }
                }
            }

            break;
        }

        if ( is_array( $current_shares )  && $current_shares ){
            remove_action( 'update_postmeta', 'wpnfps_update_postmeta' );
            foreach( $current_shares as $site_id ) {
                $remote_id = wpfnps_remote_share_id( $object_id, $site_id );

                if ( $remote_id ) {
                    switch_to_blog( $site_id );
                    update_post_meta( $remote_id, $meta_key, $meta_value );
                    restore_current_blog();
                }

                /**
                 * Fires after updating post meta on a remote site
                 *
                 * @param int    $object_id The origin object ID
                 * @param int    $site_id   The remote site ID
                 * @param int    $remote_id The remote post ID
                 * @param string $meta_key
                 * @param mixed  $meta_value
                 */
                do_action( 'wpfnps_update_remote_post_meta', $object_id, $site_id, $remote_id, $meta_key, $meta_value );
            }
            add_action( 'update_postmeta', 'wpnfps_update_postmeta', 10, 4 );
        }
    } while ( false );

}

/**
 * @param int $blog_id
 * @param int $object_id
 * @return void
 */
function wpfnps_delete_remote_share( $blog_id, $object_id ) {

    $remote_post_id = get_post_meta( $object_id, "_remote_share_id_{$blog_id}", 'single' );
    switch_to_blog( $blog_id );
    wp_delete_post( $remote_post_id );
    restore_current_blog();

}

/**
 * @param $post_id
 * @return void
 */
function wpfnps_delete_remote_images( $post_id ) {

    wp_delete_post( get_post_thumbnail_id( $post_id ) );

    $gallery_images = get_field( 'image_gallery', $post_id );

    if ( is_array( $gallery_images) && $gallery_images ) {
        foreach( $gallery_images as $image ) {
            wp_delete_post( $image['ID'] );
        }
    }

}
