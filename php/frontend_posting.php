<?php
namespace SIM\PRAYER;
use SIM;
use WP_Error;

/**
 * Checks if the current post has the prayer category
 * if so checks if it has an word attachment
 * 
 * returns an error when it does not have the month and year in the title
 */
add_filter('sim_frontend_content_validation', __NAMESPACE__.'\contentValidation', 10, 2);
function contentValidation($error, $frontEndContent){
    // do not continue if the post content contains less than 28 prayerpoints
    if(
        $frontEndContent->postType   != 'prayer' ||
        is_wp_error($error) || 
        preg_match_all('/\d{1,2}\([S|M|T|W|F]\)/i', strip_tags($frontEndContent->postContent), $matches) < 20
    ){
        return $error;
    }

    $years  = [Date('Y')-2, Date('Y')-1, Date('Y'), Date('Y')+1];
    $found  = false;
    foreach($years as $year){
        if(str_contains($frontEndContent->postTitle, strval($year))){
            $found  = true;
            break;
        }
    }

    if(!$found){
        return new WP_Error('prayer', "I guess you are submitting a post with prayerpoints?<br><br>Please make sure the year is included in the post title.");
    }

    //month year
    return $error;
}