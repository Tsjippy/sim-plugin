<?php
namespace SIM\SIGNAL;
use SIM;
use mikehaertl\shellcommand\Command;

require_once( MODULE_PATH  . 'lib/vendor/autoload.php');

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'run_daemon_action', __NAMESPACE__.'\runDaemon' );
});

function scheduleTasks(){
    SIM\scheduleTask('run_daemon_action', 'daily');
}

function runDaemon(){
	$signal		= new Signal();
	$command 	= new Command([
		'command' => $signal->path
	]);

	$command->addArg('--config', $signal->profilePath);

	$command->addArg('-u', $signal->username);

	$command->addArg('daemon');

	$command->execute();
}

// Remove scheduled tasks upon module deactivation
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	wp_clear_scheduled_hook( 'run_daemon_action' );
});