<?php
add_action( 'before_delete_post', function ( $post_id ) {

    $post_types = apply_filters( 'wpforge_post_sharing_post_types', array() );

    if ( in_array( get_post_type( $post_id ), $post_types ) ) {
       wpforge_delete_remote_shares( $post_id );
       wpforge_delete_origin_share_reference( $post_id );
    }
} );

/**
 * Delete a shared post from the remote sites when deleting the origin post.
 *
 * @param  int $post_id
 * @return void
 */
function wpforge_delete_remote_shares( $post_id ) {

    $shared_with = get_field( 'share_with', $post_id );

    foreach( $shared_with as $site_id ) {
        $remote_id = get_post_meta($post_id, "_remote_share_id_{$site_id}", 'single');

        if ($remote_id) {
            switch_to_blog($site_id);
            wpforge_delete_remote_images($remote_id);
            wp_delete_post($remote_id, "force delete");
            restore_current_blog();
        }
    }

}

/**
 * Delete the reference to the shared post on the origin blog when deleting a remote shared post.
 *
 * @param  int $post_id
 * @return void
 */
function wpforge_delete_origin_share_reference( $post_id ) {

    $shared_from = get_post_meta($post_id, '_origin_blog_id', 'single');

    if ($shared_from) {
        $origin_post_id = get_post_meta($post_id, '_origin_share_id', 'single');
        $site_id = get_current_blog_id();

        switch_to_blog($shared_from);
        delete_post_meta($origin_post_id, "_remote_share_id{$site_id}");
        restore_current_blog();
    }

}
