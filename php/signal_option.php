<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-signal-personal-settings', __NAMESPACE__ . '\signalSettings', 10, 3);
/**
 * Add message time setting to personal signal settings
 *
 * @param string $settings The existing settings
 * @param \WP_User $user The user object
 * @param array $prefs The user preferences
 *
 * @return string The updated settings
 */
function signalSettings($settings, $user, $prefs)
{
    $messageTime = '';
    if (isset($prefs['message-time'])) {
        $messageTime = $prefs['message-time'];
    }

    ob_start();
    ?>

    <label>
        <h4>
            Send me a personal message around:
        </h4>
        <input type='time' name='message-time' value='<?php esc_attr($messageTime);?>'>
    </label>
    <?php

    return $settings.ob_get_clean();
}

add_action('tsjippy-signal-before-pref-save', __NAMESPACE__ . '\beforePrevSafe', 10, 2);

/**
 * Update message time schedule before saving preferences
 *
 * @param int   $userId The user ID
 * @param array $prefs  The user preferences
 */
function beforePrevSafe($userId, $prefs)
{
    $messageSchedule    = new MessageSchedule();

    $newTime    = $prefs['message-time'] ?? null;

    $messageSchedule->update($userId, $newTime);
}
