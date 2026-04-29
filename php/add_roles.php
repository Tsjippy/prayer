<?php
namespace TSJIPPY\PRAYER;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter('tsjippy_role_description', __NAMESPACE__.'\roleDescription', 10, 2);
function roleDescription($description, $role){
    if($role == 'prayercoordinator'){
        return 'Ability to publish prayer requests';
    }
    return $description;
}