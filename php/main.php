<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    TSJIPPY\registerPostTypeAndTax('daily-message', 'daily-messages');
});

//give message coordinator acces to message items
add_filter('tsjippy-frontend-content-edit-rights', __NAMESPACE__ . '\editRights', 10, 2);
/**
 * Tweaks the edit rights for messages
 *
 * @param   bool    $editRight      Whether the user has edit rights
 * @param   array   $postCategory     The categories of the post
 *
 * @return  bool    Whether the user has edit rights
 */
function editRights($editRight, $postCategory)
{
    if (
        !$editRight                                                   &&    // If we currently have no edit right
        in_array('message-coordinator', wp_get_current_user()->roles) &&     // If we have the message coordinator role 
        get_post_type() == 'daily-message'
    ) {
        $editRight = true;
    }

    return $editRight;
}

/**
 *
 * Get the message of today
 *
 * @param   string      $plainText      Whether we shuld return the message in html or plain text
 * @param    bool       $verified       If we trust the request, default false
 * @param    string|int $date           The date or time string for which to get the request, default empty for today
 *
 * @return   array|false                An array containing the message and pictures or false if no message found
 *
 **/
function getDailyMessage($plainText = false, $verified = false, $date = '')
{
    if (!is_user_logged_in() && !$verified) {
        return false;
    }

    $family    = new TSJIPPY\FAMILY\Family();

    if (empty($date)) {
        $date = gmdate("Y-m-d");
    } else {
        // epoch
        if (is_numeric($date)) {
            $datetime    = $date;
        } else {
            // date string given
            $datetime     = strtotime($date);
        }

        $date            = gmdate("Y-m-d", $datetime);
    }

    //Get all the message posts for this date
    $posts = get_posts(
        array(
            'post_type'   => 'daily-message',
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'ASC',
            'meta_key'    => "tsjippy_date_$date",
            'numberposts' => -1,
        )
    );

    if (empty($posts)) {
        if ($plainText) {

            return [
                'message'    => 'Sorry I could not find any message for today',
                'pictures'    => []
            ];
        }
        return false;
    }

    $message  = '';
    $users    = [];
    $pictures = [];
    $urls     = [];

    foreach ($posts as $post) {
        $cats         = wp_get_post_terms($post->ID, 'daily-messages');

        // Show the category name
        foreach ($cats as $cat) {
            $message    .= "<i>$cat->name</i><br>";
        }

        // Add the heading
        $message    .= trim(explode(':', $post->post_title)[1]) . '<br>';

        // Main message
        $message    .= $post->post_content . '<br><br>';

        $users         = array_merge(get_post_meta($post->ID, 'tsjippy_user-id'), $users);
    }

    foreach ($users as $userId) {
        // family picture
        $picture = $family->getFamilyMeta($userId, 'family_picture', true);

        if (is_numeric($picture)) {
            $attachmentId    = $picture;
        } else {
            $attachmentId    = get_user_meta($userId, 'tsjippy_profile_picture', true);
            if (is_array($attachmentId)) {
                if (isset($attachmentId[0])) {
                    $attachmentId    = $attachmentId[0];
                } else {
                    $attachmentId    = 0;
                }
            }
        }

        if (is_numeric($attachmentId)) {
            $picture     = get_attached_file($attachmentId);
        } else {
            $picture     = TSJIPPY\urlToPath($attachmentId);
        }

        if (!isset($pictures[$picture])) {
            $pictures[$picture] = 1;
        }

        // user page url
        $url        = get_author_posts_url($userId);
        if ($url && !isset($urls[$url])) {
            $urls[$url] = 1;
        }
    }

    if ($plainText) {
        $message     = stripTags($message);
        $message    = str_replace(['<br>', '</br>', '</ br>', '<br />'], "\n", $message);
    }

    $params    = [
        'message'  => $message,
        'pictures' => array_keys($pictures),
        'urls'     => array_keys($urls),
        'users'    => $users
    ];

    // skip filter if we are not returning it for a signal message for today
    if ($plainText && $date == gmdate("Y-m-d")) {
        $params    = apply_filters('tsjippy-payer-after-message', $params);

        //prevent duplicate urls
        $params['urls']    = array_unique($params['urls']);

        $params['message'] = $params['message'] . "\n\n" . implode("\n", $params['urls']);
    }

    return $params;
}
