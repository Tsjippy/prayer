<?php
namespace TSJIPPY\PRAYER;
use TSJIPPY;

use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu{

    /**
     * AdminMenu constructor.
     * 
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        wp_enqueue_script('tsjippy_prayer_admin', TSJIPPY\pathToUrl(PLUGINPATH.'js/admin.min.js'), array(), PLUGINVERSION, true);

        ob_start();
        
        if(empty($this->settings['groups'])){
            $groups	= [''];
        }else{
            $groups	= $this->settings['groups'];
        }

        ?>
        <h4>Show prayer request on homepage</h4>
        <label>
            Frontpage Hook<br>
            <input type='text' name='frontpagehook' value='<?php if(isset($this->settings['frontpagehook'])){echo $this->settings['frontpagehook'];}else{echo '';}?>'>
        </label>
        <br>
        <h4>Send prayer message check</h4>
        <label>
            People whom submitted a prayer request will be send their request X days in advance to check if it needs an update <br>
            Leave empty for no check<br>
            <input type='number' name='prayercheck' value='<?php if(isset($this->settings['prayercheck'])){echo $this->settings['prayercheck'];}else{echo '';}?>'>
        </label>
        <br>
        <div class="">
            <h4>Give optional Signal group name(s) to send a daily prayer message to:</h4>
            <div class="clone-divs-wrapper">
                <?php
                foreach($groups as $index => $group){
                    ?>
                    <div class="clone-div" data-div-id="<?php echo $index;?>" style="display:flex;border: #dedede solid; padding: 10px; margin-bottom: 10px;">
                        <div class="multi-input-wrapper">
                            <label>
                                <h4 style='margin: 0px;'>Signal groupname <?php echo $index+1;?></h4>
                            </label>
                            <?php
                            if(defined('TSJIPPY\SIGNAL\SETTINGS') && TSJIPPY\SIGNAL\SETTINGS['local'] ?? false){
                                ?>
                                <select  name="groups[<?php echo $index;?>][name]">
                                    <option value="">---</option>
                                    <?php
                                    $signal 		= TSJIPPY\SIGNAL\getSignalInstance();

                                    foreach($signal->listGroups() as $g){
                                        if(empty($g->name)){
                                            continue;
                                        }
                                        $selected	= '';
                                        if($group['name'] == $g->id){
                                            $selected	= 'selected="selected"';
                                        }
                                        echo "<option value='$g->id' $selected>$g->name</option>";
                                    }
                                    ?>
                                </select>
                                <?php
                            }else{
                                ?>
                                <input type='text' name="groups[<?php echo $index;?>][name]" value='<?php if(!empty($group['name'])){echo $group['name'];}?>'>
                                <?php
                            }
                            ?>
                            <label>
                                <h4 style='margin-bottom: 0px;'>Time the message should be send</h4>
                                <input type='time' name="groups[<?php echo $index;?>][time]" value='<?php if(!empty($group['time'])){echo $group['time'];}?>'>
                            </label>
                        </div>
                        <div class='button-wrapper' style='margin:auto;'>
                            <button type="button" class="add button" style="flex: 1;">+</button>
                            <?php
                            if(count($groups)> 1){
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

    public function emails($parent){
        return false;
    }

    public function data($parent=''){

        return false;
    }

    public function functions($parent){
        if(isset($_POST['prayer-recipient']) && TSJIPPY\verifyNonce('nonce', 'send-prayer-nonce')){
            $recipient      = sanitize_text_field($_POST['prayer-recipient']);

            $prayerRequest	= prayerRequest(true, true);

            $message	 	= "The prayer request of today is:\n";
            $message 		.= $prayerRequest['message'];

            $dayPart	= "morning";
            $hour		= current_time('H');
            if($hour > 11 && $hour < 18){
                $dayPart	= 'afternoon';
            }elseif($hour > 17){
                $dayPart	= 'evening';
            }elseif($hour < 4){
                $dayPart	= 'night';
            }

            // make this available through an action to be used by the signal plugin, potentially others
            do_action(
                'tsjippy-prayer-send-message',
                "Good $dayPart ,\n\n$message", 
                $recipient, 
                $prayerRequest['pictures']
            );
        }
        $users			= get_users( [
            'meta_query' => array(
                array(
                    'key'     => 'phonenumbers',
                    'compare' => 'EXISTS'
                )
            ),
            'orderby'	=> 'meta_value',
            'order' 	=> 'ASC'
        ]);

        ob_start();
        ?>
        <h4>Send Prayer Now</h4>
        <p>
            Send a prayer message
        </p>

        <form method='POST' enctype="multipart/form-data">
            <input type='hidden' name='nonce' value='<?php echo wp_create_nonce('send-prayer-nonce');?>'>
            <label>
                Recipient: phonenumber or group id <br>
                <input type='text' name='prayer-recipient' list="recipients">
            </label>
            <datalist id="recipients">
                <?php
                foreach($this->settings['groups'] ?? [] as $group){
                    ?>
                    <option value='<?php echo esc_attr($group['name']);?>'>
                        <?php echo esc_html($group['name']);?>
                    </option>
                    <?php
                }

                foreach ($users as $user) {
                    $phones	= (array)get_user_meta($user->ID, 'phonenumbers', true);
                    foreach($phones as $phone){
                        ?>
                        <option value='<?php echo esc_attr($phone);?>'>
                            <?php echo esc_html("{$user->display_name} ({$phone})");?>
                        </option>
                        <?php
                    }
                }
                ?>
            </datalist>
            <br>
            <br>
            <button type='submit' name='send-prayer'>Send Prayer</button>
        </form>

        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Function to do extra actions from $_POST data. Overwrite if needed
     */
    public function postActions(){
        return '';
    }

    /**
     * Indexes the groups array by the group name, so it is easier to compare the old and new groups when saving the settings
     * 
     * @param array $array The array to index
     * 
     * @return array The indexed array
     */
    private function indexArray($array){
        $newArray	= [];
        foreach($array as $item){
            $newArray[$item['name']]	= $item['time'];
        }

        return $newArray;
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        scheduleTasks();

        $date			    = \Date('y-m-d');

        $oldGroups          = $this->indexArray(SETTINGS['groups'] ?? []);
        $newGroups          = $this->indexArray($this->settings['groups'] ?? []);

        // Compute the difference between the old and new groups to find out which groups have been added and which have been removed
        $added		= array_diff_assoc($newGroups, $oldGroups);
        $removed	= array_diff_assoc($oldGroups, $newGroups);
        $updated    = array_intersect(array_keys($added), array_keys($removed));

        $prayerSchedule    = new PrayerSchedule();
        foreach($removed as $recipient => $time){
            if(empty($recipient) || in_array($recipient, $updated)){
                continue;
            }
            // Remove the group from the schedule
            $prayerSchedule->delete($recipient); 
        }

        foreach($added as $recipient => $time){
            if(empty($recipient) || in_array($recipient, $updated)){
                continue;
            }

            // Add the group to the schedule
            $prayerSchedule->add($recipient, $time); 
        }

        foreach($updated as $recipient){
            if(empty($recipient)){
                continue;
            }

            // Add the group to the schedule
            $prayerSchedule->update($recipient, $added[$recipient]); 
        }

        // Mark todays chedule as outdated so it will be renewed with the new groups and times
        update_option("prayer_schedule_$date", false);
    }
}