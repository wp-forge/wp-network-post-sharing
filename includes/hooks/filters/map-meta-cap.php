<?php
/**
 * The map_meta_cap filter callback
 *
 * @link https://developer.wordpress.org/reference/hooks/map_meta_cap/
 */
add_filter( 'map_meta_cap', function( $caps, $cap, $user_id, $args ) {

    do {
        if ( current_user_can( 'create_sites' ) ) {
            break;
        }

        if( in_array( $cap, array( 'edit_post', 'delete_post' ) ) && isset( $args[0] ) ) {

            $post_id = $args[0];

            if( get_post_meta( $post_id, '_origin_blog_id' ) ) {
                //This is a shared listing and therefore is not editable or trashable
                $caps[] = 'do_not_allow';
            }

        }
    } while ( false );

    return $caps;

}, 10, 4 );