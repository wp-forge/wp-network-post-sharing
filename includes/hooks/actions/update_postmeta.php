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

    if ( '_wpforge_network_post_sharing_share_with' === $meta_key ) {
        $share_with = maybe_unserialize( $meta_value );
        $current_shares = get_field( $meta_key, $object_id );

        if ( $share_with ) {
            foreach( $share_with as $blog_id ) {
                if( $blog_id ) {
                    $remote_id = wpforge_update_post_share( $blog_id, $object_id );

                    if ( $remote_id ) {
                        update_post_meta( $object_id, "_remote_share_id_{$blog_id}", $remote_id );
                    }
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

    foreach( $gallery_images as $image ) {
        wp_delete_post( $image['ID'] );
    }

}
