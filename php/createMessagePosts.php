<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Creates the regex pattern for finding dates in all sort of formats
 */
function dateRegex()
{
    /* $year = [
        'Y' => "20(?:0[1-9]|[12]\d)",
        'y' => "(?:0[1-9]|[12]\d)"
    ];
    $years = "(?:".implode('|', $year). ")"; */
    $years = "(?:20)?(?:0[1-9]|[12]\d)";

    $month = [
        'F' => "(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|June?|July?|Aug(?:ust)?|Sept?(?:ember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)",
        //'M' => "(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sept|Oct|Nov|Dec)",
        'm' => "(?:0?[1-9]|1[0-2])",
        //'n' => "(?:[1-9]|1[0-2])"
    ];
    $months = "(?:" . implode('|', $month) . ")";

    $day = [
        'd' => "(?:0?[1-9]|[12]\d|3[01])(?:nd|th)?",
        //'j' => "(?:[1-9]|[12]\d|3[0-1]])(?:nd|th)?",
        //'D' => "(?:Sun|Mon|Tues|Tue|Tu|Wed|Thurs|Thu|Th|Fri|Sat)",
        'l' => "(?:Sun(?:day)?|Mon(?:day)?|Tue?s?(?:day)?|Wed(?:nesday)?|Thu?r?s?(?:day)?|Fri(?:day)?|Sat(?:urday)?)"
    ];
    $days = "\b(?:" . implode('|', $day) . ")\b";

    $seperators = "(?:\/|\.|-|\s|,\s)";

    $regex  = "$days$seperators$months$seperators$days$seperators?$years?|";
    $regex .= "$years$seperators$months$seperators$days|";
    $regex .= "$years$seperators$days$seperators$months|";
    $regex .= "$months$seperators$days$seperators?$years?|";
    $regex .= "$days$seperators$months$seperators?$years?";

    return $regex;
}

/**
 * Parse Message Post
 *
 * @param   object  $post   The post object of the message post
 */
function parsePostContent($post)
{
    $text        = preg_replace("/(*UTF8)(\x{002D}|\x{058A}|\x{05BE}|\x{2010}|\x{2011}|\x{2012}|\x{2013}|\x{2014}|\x{2015}|\x{2E3A}|\x{2E3B}|\x{FE58}|\x{FE63}|\x{FF0D})/mus", "-", $post->post_content);

    /**
     * build the regex
     **/

    // the date pattern itself
    $dateRegex      = dateRegex();

    // makes sure the date is not part of a bigger word and is on its own line
    $charsAfterDate = "(?=(?:\R|<|\s)).{0,20}?(?:<br>|<br \/>|<br\/>|\R)";

    // This captures the first line, the date
    $dateLine       = "(?P<date>$dateRegex)$charsAfterDate";

    // Captures the heading of the message
    $heading        = "(?P<heading>.+?)(?:<br>|<br \/>|<br\/>|\R)";

    // The actual message
    $message        = "(?P<message>.+?)";

    // the line of the next message or the end of the document
    $end            = "(?=(?:(?:$dateRegex)$charsAfterDate|$))";

    // All combined
    $re                = "/(*UTF8)$dateLine$heading$message$end/s";
    preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);

    if (count($matches) < 28) {
        return false; // Less than 28 messages found
    }

    $messages = [];

    foreach ($matches as $match) {
        $html        = $match['message'];

        $heading    = stripTags($match['heading']);
        if (!str_contains($heading, '<b>') && !str_contains($heading, '<strong>')) {
            $heading    = "<b>$heading</b>";
        }

        $userPageLinks  = new TSJIPPY\UserPageLinks($heading, false);

        $messages[$match['date']] = [
            'heading' => $heading,

            'message' => cleanMessage($html),

            'userIds' => $userPageLinks->foundUsers,
        ];
    }

    return $messages;
}

/**
 * Strips HTML tags from the content
 *
 * @param   string  $content    The content to strip tags from
 *
 * @return  string  The content with tags stripped
 */
function stripTags($content)
{
    // Content of page with all messages of this month
    return strip_tags($content, ['strong', 'b', 'em', 'i', 'details', 's', 'br']);
}

/**
 * Removes and balances html tags
 *
 * @param   string  $msg    The message to clean
 *
 * @return  string  The cleaned message
 */
function cleanMessage($msg)
{
    // < SOME TAG > one or more spaces followed by the same tag closing </ (\g1) >
    $re        = "/<([^>]*)>(?:\s|&nbsp;)*<\/\g1>/";

    //Remove empty tags
    $msg    = trim(preg_replace($re, '', $msg));

    // Balance
    $msg    = force_balance_tags($msg);

    //Remove empty tags again after balancing
    $msg    = trim(preg_replace($re, '', $msg));

    // Remove starting and ending line breaks
    $msg    = preg_replace("/(^(<br\s*\/?>)|(<br\s*\/?>\s*)+$)/", "", $msg);

    return $msg;
}

/**
 * Creates message posts from a parent post
 *
 * @param   int     $postId   The ID of the parent post
 * @param   object  $post     The parent post object
 * @param   bool    $update   Whether this is an update or a new post
 */
function createMessagePosts($postId, $post, $update)
{
    // Check if it's an autosave or a revision
    if (
        $post->post_status != 'publish' ||      // Only process if published
        wp_is_post_autosave($postId) ||
        wp_is_post_revision($postId) ||
        !empty($post->post_parent)              // only process if this is not a child itself
    ) {
        return;
    }

    $messages = parsePostContent($post);

    if (!$messages) {
        return;
    }

    // remove any children of this post
    $posts = get_posts(
        array(
            'post_type'     => 'daily-message',
            'posts_per_page' => -1,
            'post_parent'    => $post->ID
        )
    );
    foreach ($posts as $prevPost) {
        wp_delete_post($prevPost->ID, true);
    }

    // Get the categrories from post
    if (!empty($_POST['messages-ids'])) {
        $cats   = TSJIPPY\sanitize($_POST['messages-ids']);

        $cats   = array_map('intval', $cats);
    } else {
        $cats   = wp_get_post_categories($post->ID);
    }

    foreach ($messages as $date => $message) {
        $date       = gmdate(TSJIPPY\DATEFORMAT, strtotime($date));
        $postData   = array(
            'post_title'    => "Message Request for $date: {$message['heading']}",
            'post_content'  => $message['message'],
            'post_status'   => 'publish',
            'post_type'     => 'daily-message',
            'post_author'   => isset($message['userIds'][0]) ? $message['userIds'][0] : $post->post_author,
            'post_parent'   => $post->ID
        );

        // Insert the post into the database
        $postId = wp_insert_post($postData, false, false);

        if (is_wp_error($postId)) {
            TSJIPPY\printArray('Error inserting post: ' . $postId->get_error_message());
        }

        $date   = gmdate('Y-m-d', strtotime($date));
        add_post_meta($postId, "tsjippy_date_$date", $date, true);

        foreach ($message['userIds'] as $userId) {
            add_post_meta($postId, "tsjippy_user-id", $userId, false);
        }

        // Store the cat
        wp_set_post_terms($postId, $cats, 'message');
    }
}
add_action('save_post_daily-message', __NAMESPACE__ . '\createMessagePosts', 10, 3);
