<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;


if (! defined('ABSPATH')) {
    exit;
}

class MessageSchedule
{
    public string $tableName;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;

        $this->tableName = $wpdb->prefix . 'tsjippy_message_schedule';
    }

    /**
     * Create the sent messages table if it does not exist
     */
    public function createDbTables()
    {
        global $wpdb;

        if (!function_exists('maybe_create_table')) {
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

        maybe_create_table($this->tableName, $sql);
    }

    /**
     * Get the message schedule
     *
     * @return array The message schedule indexed by time with an array of recipients for each time
     */
    public function getSchedule()
    {
        $results = TSJIPPY\getFromDb("get_message_schedule", "message", "SELECT * FROM %i", $this->tableName);

        // Create an array indexed by time
        $schedule = [];
        foreach ($results as $result) {
            if (!isset($schedule[$result->time])) {
                $schedule[$result->time] = [];
            }
            $schedule[$result->time][] = $result->recipient;
        }

        ksort($schedule);

        return $schedule;
    }

    /**
     * Add message schedule session
     *
     * @param int $recipient The recipient to add to the schedule
     * @param string $time The time to add to the schedule
     *
     * @return true|\WP_Error True on success, WP_Error on failure
     *
     */
    public function add($recipient, $time)
    {
        /**
         * Double check we do not have duplicates
         */
        $existing   = TSJIPPY\getFromDb(
            "get_schedule_for_$recipient", 
            "message",
            "SELECT * FROM %i WHERE recipient = %s", 
            $this->tableName, 
            $recipient
        );

        foreach ($existing as $result) {
            // Only update if needed
            if ($result->time != $time) {
                $this->update($recipient, $time);
            }

            return true;
        }

        // Insert booking in db
        $result = TSJIPPY\insertInDb(
            $this->tableName,
            array(
                'recipient' => $recipient,
                'time'      => $time
            ),
            [
                '%s',
                '%d'
            ],
            'daily-message'
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Rebuild todays schedule
        $this->getTodaySchedule(true);

        return true;
    }

    /**
     * Update daily message schedule session
     *
     * @param int $recipient The recipient to update
     * @param string $time The time to update
     *
     * @return true|\WP_Error True on success, WP_Error on failure
     */
    public function update($recipient, $time)
    {
        // Update schedule in db
        $result = TSJIPPY\updateDbValue(
            $this->tableName,
            array(
                'time'      => $time
            ),
            array(
                'recipient' => $recipient,
            ),
            ['%s'],
            ['%s'],
            'daily-message'
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Rebuild todays schedule
        $this->getTodaySchedule(true);

        return true;
    }

    /**
     * Delete daily message schedule session
     *
     * @param int $recipient The recipient to delete from the schedule
     *
     * @return true|\WP_Error True on success, WP_Error on failure
     *
     */
    public function delete($recipient)
    {
        // Delete schedule from db
        TSJIPPY\removeFromDb(
            $this->tableName,
            array(
                'recipient' => $recipient
            ),
            [
                '%s'
            ],
            'daily-message'
        );

        // Rebuild todays schedule
        $this->getTodaySchedule(true);

        return true;
    }

    /**
     * Get the daily message schedule for today
     *
     * @param   bool    $force  Recreate the schedule even if one exists already
     *
     * @return  array           The daily message schedule for today indexed by time with an array of recipients for each time
     */
    public function getTodaySchedule($force = false)
    {
        $date     = \gmdate('y-m-d');

        $schedule = false;

        if (!$force) {
            $schedule = get_option("daily_message_schedule_$date", false);
        }

        if (empty($schedule)) {
            // Create a new schedule for today
            $schedule = $this->getSchedule();

            $time    = current_time('H:i');
            // remove all times in the schedule that are in the past
            foreach ($schedule as $t => $users) {
                if ($time > $t) {
                    unset($schedule[$t]);
                }
            }

            update_option("daily_message_schedule_$date", $schedule);
        }

        return $schedule;
    }
}
