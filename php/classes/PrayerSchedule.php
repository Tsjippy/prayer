<?php
namespace TSJIPPY\PRAYER;
use TSJIPPY;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PrayerSchedule {
    public string $tableName;

    /**
     * Constructor
     */
    public function __construct(){
        global $wpdb;

        $this->tableName        = $wpdb->prefix.'tsjippy_prayer_schedule';
    }

    /**
     * Create the sent messages table if it does not exist
     */
    public function createDbTables(){
		global $wpdb;

		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		
		//only create db if it does not exist
		$charsetCollate = $wpdb->get_charset_collate();

        // Sent messages log
		$sql = "CREATE TABLE {$this->tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            recipient longtext NOT NULL,
            time text NOT NULL,
            PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );

    }

    /**
     * Get the prayer schedule
     * 
     * @return array The prayer schedule indexed by time with an array of recipients for each time
     */
    public function getSchedule(){
        global $wpdb;

        $results = $wpdb->get_results( "SELECT * FROM {$this->tableName}");

        // Create an array indexed by time
        $schedule = [];
        foreach($results as $result){
            if(!isset($schedule[$result->time])){
                $schedule[$result->time] = [];
            }
            $schedule[$result->time][] = $result->recipient;
        }

        ksort($schedule);

        return $schedule;
    }

    /**
     * Add prayer schedule session
     * 
     * @param int $recipient The recipient to add to the schedule
     * @param string $time The time to add to the schedule
     * 
     * @return true|\WP_Error True on success, WP_Error on failure
     * 
     */
    public function add($recipient, $time){
        global $wpdb;

        /**
         * Double check we do not have duplicates
         */
        $existing   = $wpdb->get_results( $wpdb->prepare("SELECT * FROM %i WHERE recipient = %s", $this->tableName, $recipient));

        foreach($existing as $result){
            // Only update if needed
            if($result-> time != $time){
                $this->update($recipient, $time);
            }

            return true;
        }

        // Insert booking in db
        $wpdb->insert(
            $this->tableName,
            array(
                'recipient'		=> $recipient,
                'time'			=> $time
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('prayer', $wpdb->last_error);
		}

        return true;
    }

    /**
     * Update prayer schedule session
     * 
     * @param int $recipient The recipient to update
     * @param string $time The time to update
     * 
     * @return true|\WP_Error True on success, WP_Error on failure
     */
    public function update($recipient, $time){
        global $wpdb;

        // Update booking in db
        $wpdb->update(
            $this->tableName,
            array(
                'time'			=> $time
            ),
            array(
                'recipient'		=> $recipient,
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('prayer', $wpdb->last_error);
		}

        return true;
    }

    /**
    * Delete prayer schedule session
    * 
    * @param int $recipient The recipient to delete from the schedule
    *
    * @return true|\WP_Error True on success, WP_Error on failure
    *
    */
    public function delete($recipient){
        global $wpdb;

        // Delete booking from db
        $wpdb->delete(
            $this->tableName,
            array(
                'recipient' => $recipient
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('prayer', $wpdb->last_error);
		}

        return true;
    }

    /**
     * Get the prayer schedule for today
     * 
     * @return array The prayer schedule for today indexed by time with an array of recipients for each time
     */
    public function getTodaySchedule(){
        $date			= \Date('y-m-d');
        $schedule		= get_option("prayer_schedule_$date", false);

        if(empty($schedule)){
            // Create a new schedule for today
            $schedule = $this->getSchedule();

            $time	= current_time('H:i');
            // remove all times in the schedule that are in the past
	        foreach($schedule as $t => $users){
                if($time > $t){
                    unset($schedule[$t]);
                }
            }

            update_option("prayer_schedule_$date", $schedule);
        }

        return $schedule;
    }
}