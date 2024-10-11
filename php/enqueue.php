<?php
namespace SIM\PRAYER;
use SIM;

//load js and css
add_action( 'admin_enqueue_scripts', function ($hook) {
	//Only load on sim settings pages
	if(!str_contains($hook, 'sim-settings_page_sim_prayer')) {
		return;
	}

	wp_enqueue_script('sim_prayer_admin', SIM\pathToUrl(MODULE_PATH.'js/admin.min.js'), array() ,MODULE_VERSION, true);
});