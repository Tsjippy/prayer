<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim_role_description', __NAMESPACE__.'\roleDescription', 10, 2);
function roleDescription($description, $role){
    if($role == 'prayercoordinator'){
        return 'Ability to publish prayer requests';
    }
    return $description;
}