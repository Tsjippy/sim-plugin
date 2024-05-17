<?php
namespace SIM\SIGNAL;
use SIM;

/**
 * Check returns the required signal instance: cmd, dbus or jsonrpc
 */
function getSignalInstance(){
    if(str_contains(php_uname(), 'Linux')){
        $type   = SIM\getModuleOption(MODULE_SLUG, 'type');
        
        if($type && $type == 'dbus'){
            $signal	= new SignalBus();
        }else{
            $signal = new SignalJsonRpc();
        }
    }else{
		$signal = new SignalCommandLine();
	}     
    
    return $signal;
}

