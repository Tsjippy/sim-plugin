<?php
namespace SIM;

set_error_handler(function ( $errno, $errstr, $errfile, $errline ) {
    if( $errno == E_USER_NOTICE && !str_contains($errstr, '_load_textdomain_just_in_time')) {

        $message = 'You have an error notice: "%s" in file "%s" at line: "%s".' ;
        $message = sprintf($message, $errstr, $errfile, $errline);

        error_log(print_r($message, true));
        error_log(print_r(generateStackTrace(), true));
    }
});

// Function from php.net https://php.net/manual/en/function.debug-backtrace.php#112238
function generateStackTrace() {

    $e = new \Exception();

    $trace = explode( "\n" , $e->getTraceAsString() );

    // reverse array to make steps line up chronologically

    $trace = array_reverse($trace);

    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method

    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    $result = implode("\n", $result);
    $result = "\n" . $result . "\n";

    return $result;
}