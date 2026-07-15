Show a daily message on your website or send it through other channels to your audience. 

== Description ==
This plugin allows the creation of daily messages.<br>
These messages than can displayed on the website using the daily message block or send through other channels using the 'tsjippy-daily-message-send' hook.<br>
You can send a daily message easily on Signal Messenger using the tsjippy-signal plugin

== Hooks ==
# FILTERS
- tsjippy-daily-message
     * Filters the message for today
     * @param   string  $message    The message
- tsjippy-payer-after-message       
    * Filters the total of messages parameters
    * @param   array   $parameters Array containing the message, pictures, urls and user ids

# Actions
- tsjippy-daily-message-send
    * @param  string       $message    The daily message
    * @param  string|int   $recipient  The Signal group id, phonenumber of wp_user id
    * @param  array        $pictures   Pictures attached to the message

== Screenshots ==
1.

== Issues ==
Please file any issues on the wp forum or directly on Github: 
* [captcha](https://github.com/Tsjippy/captcha/issues)
* [shared functionality](https://github.com/Tsjippy/shared-functionality/issues)