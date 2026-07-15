<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    /**
     * Add the settings page to the admin menu
     *
     * @param \DOMElement $parent The parent menu slug
     * 
     * @return bool True if the settings page was added, false otherwise
     */
    public function settings($parent)
    {
        wp_enqueue_script('tsjippy_message_admin', TSJIPPY\pathToUrl(PLUGINPATH . 'js/admin.min.js'), array('tsjippy_script'), PLUGINVERSION, true);

        ob_start();

        if (empty($this->settings['groups'])) {
            $groups    = [''];
        } else {
            $groups    = $this->settings['groups'];
        }

        ?>
        <h4>
            Message prefix
        </h4>
        <label>
            Prepend the daily message with this text<br>
            <input type='text' name='message-prepend' value='<?php echo esc_attr($this->settings['message-prepend'] ?? ''); ?>'>
        </label>

        <h4>
            Send daily message check
        </h4>
        <label>
            Send a check message X days in advance to check if the message needs an update <br>
            Leave empty for no check<br>
            <input type='number' name='messagecheck' value='<?php echo esc_attr($this->settings['messagecheck'] ?? ''); ?>'>
        </label>
        <br>
        <div class="">
            <h4>
                Give optional Signal group name(s) to send a daily message to:
            </h4>
            <div class="clone-divs-wrapper">
                <?php
                foreach ($groups as $index => $group) {
                    ?>
                    <div class="clone-div" data-div-id="<?php echo esc_attr($index); ?>" style="display:flex;border: #dedede solid; padding: 10px; margin-bottom: 10px;">
                        <div class="multi-input-wrapper">
                            <label>
                                <h4 style='margin: 0px;'>Signal groupname <?php echo esc_attr($index + 1); ?></h4>
                            </label>
                            <?php
                            if (defined('TSJIPPY\SIGNAL\SETTINGS') && TSJIPPY\SIGNAL\SETTINGS['local'] ?? false) {
                                $signal    = TSJIPPY\SIGNAL\getSignalInstance();

                                $signalGroups    = (array)$signal->listGroups();

                                if (!is_wp_error($signalGroups)) {
                                    ?>
                                    <select name="groups[<?php echo esc_attr($index); ?>][name]">
                                        <option value="">---</option>
                                        <?php
                                        foreach ($signalGroups as $g) {
                                            if (empty($g->name)) {
                                                continue;
                                            }
                                            ?>
                                            <option value='<?php echo esc_attr($g->id); ?>' <?php if (($group['name'] ?? '') == $g->id) echo 'selected="selected"'; ?>>
                                                <?php echo esc_attr($g->name); ?>
                                            </option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                <?php
                                }
                            } else {
                                ?>
                                <input type='text' name="groups[<?php echo esc_attr($index); ?>][name]" value='<?php if (!empty($group['name'])) echo esc_attr($group['name']); ?>'>
                            <?php
                            }
                            ?>
                            <label>
                                <h4 style='margin-bottom: 0px;'>Time the message should be send</h4>
                                <input type='time' name="groups[<?php echo esc_attr($index); ?>][time]" value='<?php if (!empty($group['time'])) echo esc_attr($group['time']); ?>'>
                            </label>
                        </div>
                        <div class='button-wrapper' style='margin:auto;'>
                            <button type="button" class="add button" style="flex: 1;">+</button>
                            <?php
                            if (!is_wp_error($groups) && count($groups) > 1) {
                            ?>
                                <button type="button" class="remove button" style="flex: 1;">-</button>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>

        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Function to display the emails page
     *
     * @param   string  $parent The parent menu slug
     * 
     * @return  bool            True if the emails page was displayed, false otherwise
     */
    public function emails($parent)
    {
        return false;
    }

    /**
     * Function to display the emails page
     *
     * @param   string  $parent The parent menu slug
     * 
     * @return  bool            True if the emails page was displayed, false otherwise
     */
    public function data($parent = '')
    {

        return false;
    }

    /**
     * Add the functions page to the admin menu
     *
     * @param string $parent The parent menu slug
     * 
     * @return bool True if the functions page was added, false otherwise
     */
    public function functions($parent = '')
    {
        /**
         * Handle sending the daily message
         */
        // phpcs:ignore
        if (isset($_POST['message-recipient']) && TSJIPPY\verifyNonce('nonce', 'send-message-nonce')) {
            // phpcs:ignore
            $recipient = TSJIPPY\sanitize($_POST['message-recipient']);

            $message   = getDailyMessage(true, true);

            if(!$message){
                return;
            }

            $msg  = (SETTINGS['message-prepend'] ?? '') . "\n";
            $msg .= $message['message'];

            $dayPart    = "morning";
            $hour        = current_time('H');
            if ($hour > 11 && $hour < 18) {
                $dayPart    = 'afternoon';
            } elseif ($hour > 17) {
                $dayPart    = 'evening';
            } elseif ($hour < 4) {
                $dayPart    = 'night';
            }

            // make this available through an action to be used by the signal plugin, potentially others
            do_action(
                'tsjippy-daily-message-send',
                "Good $dayPart ,\n\n$msg",
                $recipient,
                $message['pictures']
            );
        }

        $users            = get_users([
            'meta_query' => array(
                array(
                    'key'     => 'tsjippy_phonenumbers',
                    'compare' => 'EXISTS'
                )
            ),
            'orderby'    => 'meta_value',
            'order'     => 'ASC'
        ]);

        TSJIPPY\addElement('h4', $parent, [], 'Send Daily Message Now');

        TSJIPPY\addElement('p', $parent, [], 'Send a daily message');

        $form   = TSJIPPY\addElement('form', $parent, ['method' => 'POST', 'enctype' => "multipart/form-data"]);

        TSJIPPY\addElement(
            'input',
            $form,
            [
                'type'  => 'hidden',
                'name'  => 'nonce',
                'value' => wp_create_nonce('send-message-nonce')
            ],
            'Send a daily message'
        );

        $label  = TSJIPPY\addElement('label', $form, [], 'Recipient: phonenumber or group id');

        TSJIPPY\addElement('br', $label);

        TSJIPPY\addElement(
            'input',
            $label,
            [
                'type'  => 'text',
                'name'  => 'message-recipient',
                'list'  => 'recipients'
            ]
        );

        $dataList   = TSJIPPY\addElement('datalist', $form, ['id' => "recipients"]);

        if (defined('TSJIPPY\SIGNAL\SETTINGS') && TSJIPPY\SIGNAL\SETTINGS['local'] ?? false) {
            $signal     = TSJIPPY\SIGNAL\getSignalInstance();
            foreach ($signal->listGroups() as $g) {
                if (empty($g->name)) {
                    continue;
                }

                TSJIPPY\addElement('option', $dataList, ['value' => $g->id], $g->name);
            }
        }

        foreach ($users as $user) {
            $phones    = get_user_meta($user->ID, 'tsjippy_phonenumbers');
            foreach ($phones as $phone) {
                TSJIPPY\addElement('option', $dataList, ['value' => $phone], "{$user->display_name} ({$phone})");
            }
        }

        TSJIPPY\addElement('br', $form);

        TSJIPPY\addElement('button', $form, ['type' => 'submit', 'name' => 'send-message'], 'Send Message');

        return true;
    }

    /**
     * Indexes the groups array by the group name, so it is easier to compare the old and new groups when saving the settings
     *
     * @param array $array The array to index
     *
     * @return array The indexed array
     */
    private function indexArray($array)
    {
        $newArray    = [];
        foreach ($array as $item) {
            $newArray[$item['name']]    = $item['time'];
        }

        return $newArray;
    }

    /**
     * Schedules the tasks for this plugin
     *
     * @param   array   $request    The sanitized request data
     */
    public function postSettingsSave($request)
    {
        $date      = \gmdate('y-m-d');

        $oldGroups = $this->indexArray(SETTINGS['groups'] ?? []);
        $newGroups = $this->indexArray($this->settings['groups'] ?? []);

        // Compute the difference between the old and new groups to find out which groups have been added and which have been removed
        $added      = array_diff_assoc($newGroups, $oldGroups);
        $removed    = array_diff_assoc($oldGroups, $newGroups);
        $updated    = array_intersect(array_keys($added), array_keys($removed));

        $messageSchedule = new MessageSchedule();
        foreach ($removed as $recipient => $time) {
            if (empty($recipient) || in_array($recipient, $updated)) {
                continue;
            }
            // Remove the group from the schedule
            $messageSchedule->delete($recipient);
        }

        foreach ($added as $recipient => $time) {
            if (empty($recipient) || in_array($recipient, $updated)) {
                continue;
            }

            // Add the group to the schedule
            $messageSchedule->add($recipient, $time);
        }

        foreach ($updated as $recipient) {
            if (empty($recipient)) {
                continue;
            }

            // Add the group to the schedule
            $messageSchedule->update($recipient, $added[$recipient]);
        }

        // Mark todays chedule as outdated so it will be renewed with the new groups and times
        update_option("daily_message_schedule_$date", false);
    }
}
