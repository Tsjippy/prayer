<?php
namespace SIM\PRAYER;
use SIM;

add_action('init', __NAMESPACE__.'\init');
function init(){
	//add action for use in scheduled task
	add_action( 'send_prayer_action', __NAMESPACE__.'\sendPrayerRequests' );
	
	//add action for use in scheduled task
	add_action( 'check_prayer_action', __NAMESPACE__.'\checkPrayerRequests' );
}

function scheduleTasks(){
    SIM\scheduleTask('send_prayer_action', 'quarterly');

	SIM\scheduleTask('check_prayer_action', 'daily');
}

function createNewSchedule($schedule){

	if($schedule !== false){
		return $schedule;
	}

	// add the new schedule
	$schedule		= (array)get_option('signal_prayers');
	$updated		= false;
	foreach($schedule as $index=>$slot){
		if(empty($slot)){
			unset($schedule[$index]);
			$updated	= true;
		}
	}

	if($updated){
		update_option('signal_prayers', $schedule);
	}

	$groups			= SIM\getModuleOption(MODULE_SLUG, 'groups');
	foreach($groups as $group){
		if(isset($schedule[$group['time']])){
			$schedule[$group['time']][]	= $group['name'];
		}else{
			$schedule[$group['time']]	= [$group['name']];
		}
	}

	// remove the old schedule
	$yesterday	= date('Y-m-d', strtotime('-1 day'));
	delete_option("prayer_schedule_$yesterday");

	return $schedule;
}

/**
 * We will send the prayer request based on the times as given by people
 * As we are not sure about the timeliness of the cron schedule we keep
 * a seperate schedule for each day to be sure everyone gets what they requested
 */
function sendPrayerRequests(){
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);

 	$prayerRequest	= prayerRequest(true);

	$message	 	= "The prayer request of today is:\n";
	$message 		.= $prayerRequest['message'];
	
	// Get the schedule for today
	$date			= \Date('y-m-d');
	$schedule		= get_option("prayer_schedule_$date");

	$schedule		= createNewSchedule($schedule);

	$time	= current_time('H:i');
	foreach($schedule as $t=>$users){
		if(is_array($users)){
			// Do not continue for times in the future
			if($t > $time){
				continue;
			}

			unset($schedule[$t]);

			foreach($users as $user){
				$dayPart	= "morning";
				$hour		= current_time('H');
				if($hour > 11 && $hour < 18){
					$dayPart	= 'afternoon';
				}elseif($hour > 17){
					$dayPart	= 'evening';
				}elseif($hour < 4){
					$dayPart	= 'night';
				}

				if(is_numeric($user)){
					$userdata	= get_userdata($user);

					if(!$userdata){
						continue;
					}
					
					$dayPart	.= " ".$userdata->first_name;
				}

				// make this available thrue an action to be used by the signal module, potentially others
				do_action(
					'sim-prayer-send-message',
					"Good $dayPart,\n\n$message", 
					$user, 
					$prayerRequest['pictures']
				);
			}
		}
	}

	update_option("prayer_schedule_$date", $schedule);
}

/**
 * Check if a prayer request needs an update
 */
function checkPrayerRequests(){
	// Get the amount of days between this check and the actual publishing
	$days			= SIM\getModuleOption('prayer', 'prayercheck');
	if(empty($days)){
		return;
	}

	// Get the actual prayer request this warning is for
	$dateTime		= strtotime("+$days day", time());
	$dateString		= date(DATEFORMAT, $dateTime);

	$prayerRequests = get_posts(
		array(
			'post_type'		=> 'prayer-request',
			'post_status'  	=> 'publish',
			'numberposts'	=> -1,
			'meta_query'    => array(
        		'relation' => 'AND',
				array(
					'key'     => 'date',
					'value'   => date('Y-m-d', $dateTime)
				),
				array(
					'key'     => 'user-id',
					'compare' => 'EXISTS',
				),
			)
		)
	);

	// loop over all found prayer requests for the date with users attached to it. 
	foreach($prayerRequests as $prayerRequest){
		$message 		= wp_strip_all_tags($prayerRequest->post_content);

		if(empty($message)){
			continue;
		}

		$signalMessage	= "Good day %name%, $days days from now your prayer request will be sent out.\n\nPlease reply to me with an updated request if needed.\n\nThis is the request I have now:\n\n$message\n\nIt will be sent on $dateString\n\nStart your reply with 'update prayer'";

		foreach(get_post_meta($prayerRequest->ID, 'user-id') as $userId){
			$user		= get_userdata($userId);
			$msg		= str_replace('%name%', $user->first_name, $signalMessage);

			// make this available thrue an action to be used by the signal module, potentially others
			do_action(
				'sim-prayer-send-message',
				$msg, 
				$user
			);
		}
	}
}

// Remove scheduled tasks upon module deactivation
add_action('sim_module_prayer_deactivated', __NAMESPACE__.'\moduleDeActivated');
function moduleDeActivated(){
	wp_clear_scheduled_hook( 'send_prayer_action' );

	wp_clear_scheduled_hook( 'check_prayer_action' );
}