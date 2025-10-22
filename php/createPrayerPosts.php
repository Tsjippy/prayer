<?php


function createPrayerPosts( $postId, $post, $update ) {
    // Check if it's an autosave or a revision
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    // remove old prayer posts with this post id
    $posts = get_posts();
    foreach($posts as $prevPost){
        wp_delete_post($prevPost->ID);
    }

    $prayerRequests = [];
    
    foreach($prayerRequests as $date => $prayerRequest){
        $postData = array(
            'post_title'    => "Prayer Request for $date",
            'post_content'  => $prayer['message'],
            'post_status'   => 'publish', // or 'draft', 'pending', 'private'
            'post_type'     => 'prayer',    // or 'page', 'custom_post_type'
            'post_author'   => isset($prayer['userid']) ? $prayer['userid'] : $post->post_author,         // ID of the author
            'post_parent'.   => $post->ID
        );
        
        // Insert the post into the database
        $postId = wp_insert_post( $postData );
        
        if ( is_wp_error( $postId ) ) {
            echo 'Error inserting post: ' . $postId->get_error_message();
        } else {
            echo 'Post inserted successfully with ID: ' . $postId;
        }
        
        
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
