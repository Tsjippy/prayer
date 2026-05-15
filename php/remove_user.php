<?php
namespace TSJIPPY\PRAYER;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('delete_user', __NAMESPACE__.'\onUserDelete');
/**
 * Function to handle user deletion and update prayer schedule accordingly
 *
 * @param int $userId The ID of the user being deleted
 */
function onUserDelete($userId){
	$prayerSchedule    = new PrayerSchedule();

	$prayerSchedule->delete($userId);
}