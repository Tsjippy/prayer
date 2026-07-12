<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', __NAMESPACE__ . '\initBlocks');
function initBlocks()
{
    register_block_type(
        'tsjippy-daily-message/show',
        array(
            'title'           => __( 'Daily Message', '%TEXTDOMAIN%' ),
            'attributes'      => array(
                'title'   => array(
                    'label'   => __( 'Title', '%TEXTDOMAIN%' ),
                    'type'    => 'string',
                    'default' => 'Today`s Message',
                ),
                'default-message'   => array(
                    'label'   => __( 'Default Message', '%TEXTDOMAIN%' ),
                    'type'    => 'string',
                    'default' => '',
                )
            ),
            'render_callback' => __NAMESPACE__.'\dailyMessage',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'message'
        )
    );
}

/**
 * Displays the message of the day on the frontpage
 * 
 * @param   array   $attributes     Block attributes
 */
function dailyMessage($attributes)
{
    // Get the message of the day, add extra messages to it, replace names with urls
    $message    = getDailyMessage();
    if (!$message) {
        if(($_REQUEST['action'] ?? $_REQUEST['context'] ?? '') == 'edit'){
            return "<div class='warning'>No Message Found</div>";
        }elseif($attributes['default-message']){
            return "<div>".$attributes['default-message']."</div>";
        }

        return;
    }

    $filteredMessage = apply_filters('tsjippy-daily-message', $message['message']);
    $userPageLinks   = new TSJIPPY\UserPageLinks($filteredMessage, true);
    $msg             = $userPageLinks->string;

    foreach ($message['pictures'] as $index => $path) {
        $url        = $message['urls'][$index];
        $pictureUrl = TSJIPPY\pathToUrl($path);

        if (!$pictureUrl) {
            continue;
        }

        $picture = "<img width='50' height='50' src='$pictureUrl' class='attachment-avatar size-avatar' alt='' style='border-radius: 50%;' decoding='async'/>";
        $msg     = "<a href='$url'>$picture</a>$msg";
    }

    wp_enqueue_style('tsjippy_message_frontpage', TSJIPPY\pathToUrl(PLUGINPATH . 'css/frontpage.min.css'), array(), PLUGINVERSION);

    ob_start();
    ?>
    <div id='daily-message'>
        <h3 id='message-title'>
            <?php echo wp_kses_post($attributes['title']);?>
        </h3>
        <p>
            <?php echo wp_kses_post($msg); ?>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
