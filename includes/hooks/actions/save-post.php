<?php

add_action( 'save_post', 'wpfnps_save_post' );

/**
 * @param  int $post_id
 * @return int
 */
function wpfnps_save_post( $post_id ) {

    do {
        if ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            break;
        }

        if ( defined('DOING_AJAX' ) ) {
            break;
        }

        $post_types = apply_filters( 'wpforge_network_post_sharing_post_types', array() );


        if ( in_array( get_post_type( $post_id ), $post_types ) ) {

            if ( ! current_user_can('edit_post', $post_id ) ) {
                break;
            }

            $data = filter_input_array( INPUT_POST, array(
                'wpfnps_share_with' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags'  => FILTER_REQUIRE_ARRAY,
                ),
            ) );

            if ( $data && $data['wpfnps_share_with'] ) {
                remove_action( 'save_post', 'wpforge_network_post_sharing_save_post' );
                update_post_meta( $post_id, '_wpforge_network_post_sharing_share_with', $data['wpfnps_share_with'] );
                add_action( 'save_post', 'wpforge_network_post_sharing_save_post' );
            }
        }

    } while ( false );

    return $post_id;

}

/**
 * @param int   $blog_id
 * @param int   $post_id
 * @return int
 */
function wpfnps_update_post_share($blog_id, $post_id ) {

    $post      = get_post( $post_id, ARRAY_A );
    $remote_id = get_post_meta( $post_id, "_remote_share_id_{$blog_id}", 'single' );
    $post_meta = get_post_meta( $post_id );
    $post_meta = array_merge( $post_meta, array(
        '_origin_blog_id'       => array( get_current_blog_id() ),
        '_origin_share_id'      => array( $post_id ),
        '_origin_currency_code' => array( get_field( 'currency_code', 'options' ) ),
    ) );

    $post_meta = apply_filters( 'wpforge_save_remote_post_meta', $post_meta, $remote_id, $post_id );

    $taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
    $terms      = array();

    /**
     * @var WP_Taxonomy $taxonomy
     */
    foreach ( $taxonomies as $taxonomy ) {
        $temp = get_the_terms( $post_id, $taxonomy );

        if ( is_array( $temp ) ) {
            $terms[$taxonomy] = array_map( function( $term ) {
               return $term->name;
            },  $temp );
        }
    }

    $post_images = wpforge_prepare_post_images( $post_id );

    switch_to_blog( $blog_id );

    $remote_id = wpforge_maybe_create_new_remote_listing( $remote_id, $post );

    if ( $remote_id && ! is_wp_error( $remote_id ) ) {
        if ( $post_images ) {
            $post_meta['image_gallery'] = array( wpfnps_sideload_images( $post_images['gallery'], $remote_id ) );
            $post_meta['_thumbnail_id'] = wpfnps_sideload_images( $post_images['featured'], $remote_id );
        }

        wpforge_save_remote_post_meta( $remote_id, $post_meta );

        foreach( $terms as $taxonomy => $term ) {
            wp_set_object_terms( $remote_id, $term, $taxonomy );
        }
    }

    restore_current_blog();

    return intval( $remote_id );

}

/**
 * @param  int   $remote_id
 * @param  array $aircraft
 * @return int|WP_Error
 */
function wpforge_maybe_create_new_remote_listing( $remote_id, $aircraft ) {

    remove_action( 'save_post', 'wpfnps_save_post' );

    if ( $remote_id ) {
        //update existing post
        $aircraft['ID'] = $remote_id;
        $remote_id      = wp_update_post( $aircraft );
    } else {
        //insert a new post
        unset( $aircraft['ID'] );
        $remote_id = wp_insert_post( $aircraft );
    }

    add_action( 'save_post', 'wpfnps_save_post' );

    return $remote_id;

}

/**
 * @param  int    $post_id
 * @return string[]
 */
function wpforge_prepare_post_images($post_id ) {

    $aircraft_images = array(
        'featured' => null,
        'gallery'  => array(),
    );

    // Get the featured image URL so that it can be sideloaded on the remote site
    if ( get_post_thumbnail_id( $post_id ) ) {
        $aircraft_images['featured'] = array( wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' ) );
    }

    // And then do the same with the image gallery
    $gallery = get_field( 'image_gallery', $post_id );

    if ( $gallery ) {
        foreach( $gallery as $image ) {
            $aircraft_images['gallery'][] = wp_get_attachment_image_url( $image['ID'], 'full' );
        }
    }

    return $aircraft_images;

}

/**
 * @param int   $remote_id
 * @param array $meta
 */
function wpforge_save_remote_post_meta( $remote_id, $meta ) {

    $exclude = apply_filters( 'wpforge_save_remote_post_meta', array( '_edit_lock', '_edit_last', 'thumbnail_id', 'share_with', '_share_with' ) );

    remove_action( 'update_post_meta', 'wpnfps_update_postmeta' );
    foreach( $meta as $meta_key => $meta_values ) {
        if ( ! in_array( $meta_key, $exclude ) && ! preg_match( '#^_remote_share_id_\d*$#', $meta_key ) ) {
            foreach( $meta_values as $entry ) {
                update_post_meta( $remote_id, $meta_key, $entry );
            }
        }
    }

    add_action( 'update_postmeta', 'wpnfps_update_postmeta', 10, 4 );

    do_action( 'wpforge_after_save_remote_post_meta', $remote_id, $meta );
}

/**
 * @param  string[] $image_urls
 * @return int[]
 */
function wpfnps_sideload_images( $image_urls, $post_id ) {

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    global $wpdb;

    $image_ids = array();

    if ( is_array( $image_urls ) && ! empty( $image_urls ) ) {
        foreach( $image_urls as $image_url ) {
            $sql    = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_source_url' AND meta_value = %s";
            $result =  $wpdb->get_row( $wpdb->prepare( $sql, $image_url ) );

            do {
                if ( $result ) {
                    $image_ids[] = $result->post_id;
                    break;
                }

                $image_id = media_sideload_image( $image_url, $post_id, null, 'id' );

                if ( $image_id ) {
                    $image_ids[] = $image_id;
                }
            } while ( false );
        }
    }

    return $image_ids;

}

/**
 * @param  int[]  $image_ids The local image attachment IDs
 * @param  int    $remote_site_id
 * @param  int    $remote_post_id
 * @param  string $meta_key The gallery meta key
 * @return void
 */
function wpfnps_share_image_gallery( $image_ids, $remote_site_id, $remote_post_id, $meta_key ) {

    $image_urls = array();

    foreach( $image_ids as $image_id ) {
        $image_urls[] = wp_get_attachment_image_url( $image_id, 'full' );
    }

    if ( $image_urls ) {
        switch_to_blog( $remote_site_id );

        $remote_image_ids = wpfnps_sideload_images( $image_urls, $remote_post_id );

        if ( $remote_image_ids ) {
            update_post_meta( $remote_post_id, $meta_key, $remote_image_ids );
        }

        restore_current_blog();
    }

}
