<?php

namespace TSJIPPY\PRAYER;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-personal-signal-settings', __NAMESPACE__ . '\signalSettings', 10, 3);
/**
 * Add prayer time setting to personal signal settings
 *
 * @param string $settings The existing settings
 * @param \WP_User $user The user object
 * @param array $prefs The user preferences
 *
 * @return string The updated settings
 */
function signalSettings($settings, $user, $prefs)
{
    $prayerTime = '';
    if (isset($prefs['prayertime'])) {
        $prayerTime = $prefs['prayertime'];
    }

    $settings   .= "<label>";
    $settings   .= "<h4>Send me a personal prayer request reminder around:</h4>";
    $settings   .= "<input type='time' name='prayertime' value='$prayerTime'>";
    $settings   .= "</label>";

    return $settings;
}

add_action('tsjippy-signal-before-pref-save', __NAMESPACE__ . '\beforePrevSafe', 10, 2);

/**
 * Update prayer time schedule before saving preferences
 *
 * @param int $userId The user ID
 * @param array $prefs The user preferences
 */
function beforePrevSafe($userId, $prefs)
{
    $prayerSchedule    = new PrayerSchedule();

    $newTime    = $prefs['prayertime'] ?? null;

    $prayerSchedule->update($userId, $newTime);
}
