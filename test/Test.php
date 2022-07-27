<?php
namespace zot\test;

class Test {
	public function assertTrue($cond, $failMsg="") {
        print $cond ? debug_backtrace()[1]['function']." PASS\n" : debug_backtrace()[1]['function']." FAIL $failMsg\n";
    }
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }
    
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

?>