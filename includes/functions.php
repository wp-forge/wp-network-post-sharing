<?php
// Automatically load all PHP files in the 'includes/hooks/' directory
$iterator = new RecursiveDirectoryIterator( __DIR__ . '/hooks' );
foreach ( new RecursiveIteratorIterator( $iterator ) as $file ) {
	if ( $file->getExtension() === 'php' ) {
		require $file;
	}
}

/**
 * @param int $post_id
 *
 * @return int[]
 */
function wpfnps_share_with( $post_id = null ) {

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	return maybe_unserialize( get_post_meta( $post_id, '_wpforge_network_post_sharing_share_with', 'single' ) );

}

/**
 * @return array
 */
function wpfnps_post_types() {

	return $post_types = wp_parse_args( apply_filters( 'wpforge_network_post_sharing_post_types', array() ) );

}

/**
 * @param int $post_id
 *
 * @return array
 */
function wpfnps_get_shares( $post_id ) {

	return maybe_unserialize( get_post_meta( $post_id, '_wpforge_network_post_sharing_share_with', 'single' ) );

}


/**
 * Delete a shared post from the remote sites when deleting the origin post.
 *
 * @param int $post_id
 *
 * @return void
 */
function wpfnps_delete_remote_shares( $post_id ) {

	$shared_with = wpfnps_get_shares( $post_id );

	if ( is_array( $shared_with ) && $shared_with ) {
		foreach ( $shared_with as $site_id ) {
			$remote_id = get_post_meta( $post_id, "_remote_share_id_{$site_id}", 'single' );

			if ( $remote_id ) {
				switch_to_blog( $site_id );
				wpfnps_delete_remote_images( $remote_id );
				wp_delete_post( $remote_id, "force delete" );
				restore_current_blog();
			}
		}
	}

}

/**
 * Delete the reference to the shared post on the origin blog when deleting a remote shared post.
 *
 * @param int $post_id
 *
 * @return void
 */
function wpfnps_delete_origin_share_reference( $post_id ) {

	$shared_from = get_post_meta( $post_id, '_origin_blog_id', 'single' );

	if ( $shared_from ) {
		$origin_post_id = get_post_meta( $post_id, '_origin_share_id', 'single' );
		$site_id        = get_current_blog_id();

		switch_to_blog( $shared_from );

		delete_post_meta( $origin_post_id, "_remote_share_id{$site_id}" );
		$shares = wpfnps_get_shares( $origin_post_id );

		// Also remove the blog id from the origin list of shares
		if ( is_array( $shares ) ) {
			remove_action( 'update_postmeta', 'wpnfps_update_postmeta' );
			update_post_meta( $post_id, '_wpforge_network_post_sharing_share_with', array_diff( $shares, array( $site_id ) ) );
			add_action( 'update_postmeta', 'wpnfps_update_postmeta', 10, 4 );
		}

		restore_current_blog();
	}

}

/**
 * @param int $post_id
 * @param int $site_id
 *
 * @return int
 */
function wpfnps_remote_share_id( $post_id, $site_id ) {

	return get_post_meta( $post_id, "_remote_share_id_{$site_id}", 'single' );

}
