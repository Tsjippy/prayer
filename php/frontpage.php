<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-theme-frontpage-before-main-content', __NAMESPACE__ . '\beforeMainContent', 5);
/**
 * Displays the message of the day on the frontpage
 */
function beforeMainContent()
{
    if (!is_user_logged_in()) {
        return;
    }

    // Get the message of the day, add extra messages to it, replace names with urls
    $message    = getDailyMessage();
    if (!$message) {
        return;
    }

    $filteredMessage = apply_filters('tsjippy-daily-message', $message['message']);
    $userPageLinks   = new TSJIPPY\UserPageLinks($filteredMessage, true);
    $msg         = $userPageLinks->string;

    foreach ($message['pictures'] as $index => $path) {
        $url        = $message['urls'][$index];
        $pictureUrl = TSJIPPY\pathToUrl($path);

        if (!$pictureUrl) {
            continue;
        }

        $picture    = "<img width='50' height='50' src='$pictureUrl' class='attachment-avatar size-avatar' alt='' style='border-radius: 50%;' decoding='async'/>";
        $msg    = "<a href='$url'>$picture</a>$msg";
    }

    wp_enqueue_style('tsjippy_message_frontpage', TSJIPPY\pathToUrl(PLUGINPATH . 'css/frontpage.min.css'), array(), PLUGINVERSION);

    ?>
    <div id='daily-message'>
        <h3 id='message-title'>
            Today's Message</h3>
        <p>
            <?php echo wp_kses_post($msg); ?>
        </p>
    </div>
    <?php
}
