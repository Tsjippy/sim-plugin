<?php
namespace SIM\SIGNAL;
use SIM;

/**
 * Check returns the required signal instance: cmd, dbus or jsonrpc
 */
function getSignalInstance(){
    SIM\printArray("");
    if(str_contains(php_uname(), 'Linux')){
        SIM\printArray("Linus");
        $type   = SIM\getModuleOption(MODULE_SLUG, 'type');
        SIM\printArray($type);
        
        if($type && $type == 'dbus'){
            $signal	= new SignalBus();
        }else{
            $signal = new SignalJsonRpc();
        }
    }else{
        SIM\printArray("Command line");
		$signal = new SignalCommandLine();
	}     
    
    return $signal;
}

