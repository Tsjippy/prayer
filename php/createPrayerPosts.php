<?php


function createPrayerPosts( $postId, $post, $update ) {
    // Check if it's an autosave or a revision
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Perform actions only when updating an existing post
    if ( $update ) {
        // Your custom code for post updates
        // For example, update custom meta fields, send notifications, etc.
        update_post_meta( $post_id, 'my_custom_field', 'new_value' );
    } else {
        // Your custom code for new post creation
        // For example, set default custom meta fields
        add_post_meta( $post_id, 'my_custom_field', 'default_value', true );
    }
}
add_action( 'save_post', __NAMESPACE__.'\createPrayerPosts', 10, 3 );
