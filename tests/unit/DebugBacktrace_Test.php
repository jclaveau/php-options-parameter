<?php
namespace JClaveau;

// call outside test
$outside_bt        = DebugBacktrace::getBacktrace(['limit' => 2]);
$native_outside_bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
$global_caller     = DebugBacktrace::getCaller();

// call in closure
$function_bt;
$native_function_bt;
$function_caller;
$closure = function() use (&$function_bt, &$native_function_bt, &$function_caller) {
    $function_bt        = DebugBacktrace::getBacktrace(['limit' => 2]);
    $native_function_bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $function_caller    = DebugBacktrace::getCaller();
};
$closure();


class DebugBacktrace_Test extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_outside_getBacktrace()
    {
        global $outside_bt, $native_outside_bt;

        $this->assertEquals(
            array_map(function ($call) {
                return $call['function'];
            }, $native_outside_bt),
            array_reverse( array_map(function ($call) {
                return $call['function'];
            }, $outside_bt) )
        );
    }

    /**
     */
    public function test_closure_getBacktrace()
    {
        global $function_bt, $native_function_bt;

        $this->assertEquals(
            array_map(function ($call) {
                return $call['function'];
            }, $native_function_bt),
            array_reverse( array_map(function ($call) {
                return $call['function'];
            }, $function_bt) )
        );
    }

    /**
     */
    public function test_getBacktrace()
    {
        $bt = DebugBacktrace::getBacktrace(['limit' => 2]);

        // var_export($bt);

        $this->assertEquals(
            array (
                0 => array (
                    'file' => 'php-options-parameter/vendor/phpunit/phpunit/src/Framework/TestCase.php',
                    'line' => 1062,
                    'function' => 'invokeArgs',
                    'class' => 'ReflectionMethod',
                    'type' => '->',
                    'call' => 'ReflectionMethod->invokeArgs()',
                ),
                1 => array (
                    'function' => 'test_getBacktrace',
                    'class' => 'JClaveau\\DebugBacktrace_Test',
                    'type' => '->',
                    'file' => NULL,
                    'line' => NULL,
                    'call' => 'JClaveau\\DebugBacktrace_Test->test_getBacktrace()',
                ),
            ),
            $bt
        );

        $native_bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );

        $this->assertEquals(
            array_map(function ($call) {
                return $call['function'];
            }, $native_bt),
            array_reverse( array_map(function ($call) {
                return $call['function'];
            }, $bt) )
        );
    }

    /**
     */
    public function test_getCaller()
    {
        $caller = DebugBacktrace::getCaller();

        $this->assertEquals(
            array (
                'file' => 'php-options-parameter/vendor/phpunit/phpunit/src/Framework/TestCase.php',
                'line' => 1062,
                'function' => 'invokeArgs',
                'class' => 'ReflectionMethod',
                'type' => '->',
                'call' => 'ReflectionMethod->invokeArgs()',
            ),
            $caller
        );

        global $global_caller;
        // var_export($global_caller);

        $this->assertEquals(
            array (
                'file' => 'php-options-parameter/vendor/phpunit/phpunit/src/Util/Fileloader.php',
                'line' => 36,
                'function' => 'load',
                'class' => 'PHPUnit_Util_Fileloader',
                'type' => '::',
                'call' => 'PHPUnit_Util_Fileloader::load()',
            ),
            $global_caller
        );

        global $function_caller;
        // var_export($function_caller);

        $this->assertEquals(
            array (
                'file' => 'php-options-parameter/vendor/phpunit/phpunit/src/Util/Fileloader.php',
                'line' => 52,
                'args' =>
                array (
                    0 => '/home/jean/dev/mediabong/apps/php-options-parameter/tests/unit/DebugBacktrace_Test.php',
                ),
                'function' => 'include_once',
                'call' => 'include_once()',
                'class' => null,
            ),
            $function_caller
        );
    }

   /**/
}
