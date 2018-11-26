<?php
namespace JClaveau;
use       JClaveau\Exceptions\UsageException;

class NumberableObject
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toDouble($options=[])
    {
        // $options = OptionsParameter::define([
        $options = OptionsParameter::define([
            'debug' => ['default' => false, true],
        ], $options);

        // var_export( $options, !true);

        try {
            throw new \Exception('exception thrown without debug option');
        }
        catch (\Exception $e) {
            if ($options->useOption('debug')) {
                throw new \Exception('exception thrown with debug option');
            }
            else {
                throw $e;
            }
        }
        finally {
            $options->useOption('debug');
        }
    }


    public function toNumber($options=[])
    {
        // $options = OptionsParameter::define([
        $options = OptionsParameter::define([
            'return'        => ['default' => 'double', 'int'],
            'print_options' => ['default' => false, true,]
        ], $options);

        if ($options->useOption('print_options')) {
            print_r($options);
            // exit;
        }


        $value = $this->toDouble($options);

        switch ($options->useOption('return')) {
            case 'int':
                return (int) $value;
                break;
            case 'double':
                return (double) $value;
                break;
        }
    }

    public function methodUsingUndefinedOption($options=[])
    {
        // $options = OptionsParameter::define([
        $options = OptionsParameter::define([
        ], $options);

        if ($options->useOption('my_undefined_option')) {
        }
    }

    /**/
}

class OptionsParameter_Test extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_define()
    {
        $options = OptionsParameter::define([
            'debug'  => ['default' => false, true],
            'market' => ['default' => 'fr', 'us'],
        ], ['debug']);

        $this->assertEquals( $options->getOptionValue('debug'), true );
        $this->assertEquals( $options->getOptionValue('market'), 'fr' );
    }

    /**
     */
    public function test_useOption()
    {
        $options = OptionsParameter::define([
            'debug'  => ['default' => false, true],
            'market' => ['default' => 'fr', 'us'],
        ], ['debug']);

        $this->assertEquals( true, $options->useOption('debug') );

        $this->assertEquals('fr',  $options->listRemainingOptions()['market']['value']);
        $this->assertEquals(false, $options->listRemainingOptions()['market']['used']);
        $this->assertEquals(
            ['default' => 'fr', 'us'],
            $options->listRemainingOptions()['market']['possibilities']
        );
    }

    /**
     */
    public function test_useOption_arrayAccess()
    {
        $options = OptionsParameter::define([
            'debug'  => ['default' => false, true],
            'market' => ['default' => 'fr', 'us'],
        ], ['debug']);

        $this->assertEquals( true, $options['debug'] );

        $this->assertEquals('fr',  $options->listRemainingOptions()['market']['value']);
        $this->assertEquals(false, $options->listRemainingOptions()['market']['used']);
        $this->assertEquals(
            ['default' => 'fr', 'us'],
            $options->listRemainingOptions()['market']['possibilities']
        );
    }

    /**
     */
    public function test_useRemainingOptions()
    {
        $options = OptionsParameter::define([
            'debug'  => ['default' => false, true],
            'market' => ['default' => 'fr', 'us'],
        ], ['debug']);

        $this->assertEquals([
                'debug'  => true,
                'market' => 'fr',
            ],
            $options->useRemainingOptions()
        );

        $this->assertEquals([],  $options->listRemainingOptions());
    }

    /**
     */
    public function test_use_options_at_2_levels_depth()
    {
        $obj = new NumberableObject(['lalala']);
        try {
            $obj->toNumber([]);
            $this->assertFalse(true, "An exception must already be thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals(
                "exception thrown without debug option",
                $e->getMessage()
            );
        }

        try {
            $obj->toNumber(['debug']);
            $this->assertFalse(true, "An exception must already be thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals(
                "exception thrown with debug option",
                $e->getMessage()
            );
        }
    }

    /**
     */
    public function test_use_undefined_option()
    {
        $obj = new NumberableObject(['lalala']);

        try {
            $obj->methodUsingUndefinedOption();
            $this->assertFalse(true, "An exception must already be thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals(
                "Trying to get avalue of an option which is not supported: 'my_undefined_option'",
                $e->getMessage()
            );
        }
    }

    /**/
}
