<?php
/**
 * The map_meta_cap filter callback
 *
 * @link https://developer.wordpress.org/reference/hooks/map_meta_cap/
 */
add_filter( 'map_meta_cap', 'wpfnps_map_meta_cap', 10, 4 );

function wpfnps_map_meta_cap( $caps, $cap, $user_id, $args ) {

    remove_filter( 'map_meta_cap', 'wpfnps_map_meta_cap' );

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

    add_filter( 'map_meta_cap', 'wpfnps_map_meta_cap', 10, 4 );

    return $caps;

}
