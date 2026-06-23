<?php

namespace TSJIPPY\PRAYER;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-theme-frontpage-before-main-content', __NAMESPACE__ . '\beforeMainContent', 5);
function beforeMainContent()
{
    if (!is_user_logged_in()) {
        return;
    }

    // Get the prayer request of the day, add extra messages to it, replace names with urls
    $prayerRequest    = prayerRequest();
    if (!$prayerRequest) {
        return;
    }

    $filteredMessage    = apply_filters('tsjippy-prayer-message', $prayerRequest['message']);
    $userPageLinks        = new TSJIPPY\UserPageLinks($filteredMessage, true);
    $message            = $userPageLinks->string;

    foreach ($prayerRequest['pictures'] as $index => $path) {
        $url        = $prayerRequest['urls'][$index];
        $pictureUrl = TSJIPPY\pathToUrl($path);

        if (!$pictureUrl) {
            continue;
        }

        $picture    = "<img width='50' height='50' src='$pictureUrl' class='attachment-avatar size-avatar' alt='' style='border-radius: 50%;' decoding='async'/>";
        $message    = "<a href='$url'>$picture</a>$message";
    }

    wp_enqueue_style('tsjippy_prayer_frontapeg', TSJIPPY\pathToUrl(TSJIPPY\PLUGINPATH . 'css/frontpage.min.css'), array(), PLUGINVERSION);

?>
    <div id='prayer-request'>
        <h3 id='prayertitle'>Today's Prayer Request</h3>
        <p>
            <?php echo wp_kses_post($message); ?>
        </p>
    </div>
<?php
}
