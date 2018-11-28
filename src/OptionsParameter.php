<?php
namespace JClaveau;

/**
 * This class provides tools to simplify the use of options in methods, following
 * the parameter object design pattern.
 *
 * @todo UsageExceptions
 */
class OptionsParameter implements \ArrayAccess
{
    /** @var array $options_mapping */
    protected $options_mapping = [];

    /** @var array $backtrace The stack is almost empty in destruct so we save it manually */
    protected $backtrace;

    /** @var bool $disabled_destruct_checking  */
    protected $disabled_destruct_checking = !false;

    /**
     * @param array                 $options_mapping
     * @param array|OptionsParameter $options_values
     */
    public function __construct(array $options_mapping, $options_values)
    {
        $this->backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );

        if ($options_mapping)
            $this->supportOptions($options_mapping);

        if ($options_values instanceof static) {
            // Options which were defined in the caller are not passed to
            // the current function. This make the same option definable
            // at multiple steps of a call stack (and usable only by the first one).
            $options_values = $options_values->undefinedOptionsValues();
        }

        if (is_array($options_values)) {
            $this->setOptionValues($options_values);
        }
        else {
            throw new \InvalidArgumentException(
                 "Options values must be an array or an instance of " . __CLASS__
                ." instead of: ".var_export($options_values, true)
            );
        }
    }

    /**
     * @param array                 $options_mapping
     * @param array|OptionsParameter $options_values
     */
    public static function define(array $options_mapping, $options_values)
    {
        return new static(...func_get_args());
    }

    /**
     * @param  array $options_mapping
     *
     * @return $this
     */
    public function supportOptions(array $options_mapping)
    {
        if (empty($options_mapping)) {
            throw new \InvalidArgumentException(
                "Trying to set an empty set of options to support"
            );
        }

        foreach ($options_mapping as $option_name => $option_properties) {
            if ( ! is_array($option_properties)) {
                throw new \InvalidArgumentException(
                    "The properties of the option '$option_name' must be an array instead of: "
                    .var_export($option_properties, true)
                );
            }

            $this->supportOption($option_name, $option_properties);
        }

        return $this;
    }

    /**
     * @param  string          $option_name
     * @param  array|callable  $option_prosibilities
     *
     * @return $this
     */
    protected function supportOption($option_name, $option_possibilities)
    {
        if ( ! is_string($option_name) || ! $option_name) {
            throw new \InvalidArgumentException(
                "The name of the option must be a non empty string instead of: "
                .var_export($option_name, true)
            );
        }

        if ( ! $option_possibilities) {
            throw new \InvalidArgumentException(
                "Missing possibilities for the method option '$option_name': "
                .var_export($option_possibilities, true)
            );
        }

        // $bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS , 4);
        // $caller = \DebugBacktrace::getCaller();

        $option = [
            'name'  => $option_name,
            'used'  => false,
            'defined_at' => DebugBacktrace::getCaller([
                'ignore_while' => function ($backtrace_call, $backtrace_call_index) {
                    // print_r($backtrace_call);
                    return isset($backtrace_call['class'])
                        && (
                                $backtrace_call['class'] === __CLASS__
                            ||  $backtrace_call['class'] === DebugBacktrace::class
                        )
                        ;
                }
            ]),
        ];

        if (is_array($option_possibilities)) {
            $option['possibilities'] = $option_possibilities;
            $option['validator'] = function ($value, $option_possibilities) {
                return in_array($value, $option_possibilities, true);
            };
        }
        elseif (is_callable($option_possibilities)) {
            $option['validator'] = $option_possibilities;
        }
        else {
            throw new \InvalidArgumentException(
                "Unhandled type of possibilities for the method option '$option_name': "
                . var_export($option_possibilities, true)
            );
        }

        $this->options_mapping[$option_name] = $option;

        if (array_key_exists('default', $option_possibilities)) {
            $this->setOptionValue($option_name, $option_possibilities['default']);
        }

        return $this;
    }

    /**
     * Set the values of some supported options
     *
     * @param  array $values
     *
     * @return $this
     */
    public function setOptionValues(array $values)
    {
        foreach ($values as $option_name => $option_value) {
            if (is_numeric($option_name)) {
                // Non associative values are considered as boolean in a sequential
                // expression. Example: ['debug' => 'debug_level', 'stop_on_kpi_exception']
                // 'stop_on_kpi_exception' <=> 'stop_on_kpi_exception' => true
                $option_name  = $option_value;
                $option_value = true;
            }
            $this->setOptionValue($option_name, $option_value);
        }

        return $this;
    }

    /**
     * Set the value of a supported option
     *
     * @todo   call user_func_array for validator to support strings or methods
     *
     * @param  mixed $value
     *
     * @return $this
     */
    public function setOptionValue($option_name, $value)
    {
        if ( isset($this->options_mapping[$option_name])) {
            $option = $this->options_mapping[$option_name];
            if ( ! call_user_func_array($option['validator'], [$value, $option['possibilities']])) {
                throw new \InvalidArgumentException(
                    "Trying to set an unhandled value for the option '$option_name': "
                    . var_export($value, true) ."\n"
                    . var_export($this->options_mapping[$option_name], true)
                );
            }
        }
        else {
            $this->options_mapping[$option_name] = [
                'name' => $option_name,
            ];
        }

        $this->options_mapping[$option_name]['value'] = $value;

        return $this;
    }

    /**
     * Retrieves the value of an option (or the default one) and flag it as "used".
     *
     * @param  string $option_name
     *
     * @return mixed The value
     */
    public function useOption($option_name)
    {
        $value = $this->getOptionValue($option_name);

        $this->options_mapping[$option_name]['used'] = true;

        return $value;
    }

    /**
     *
     * @return array The list of options
     */
    public function listRemainingOptions()
    {
        return array_filter($this->options_mapping, function($option_properties) {
            if ($option_properties['used'])
                return false;

            if (!array_key_exists('value', $option_properties))
                return false;

            if (   array_key_exists('default', $option_properties)
                && $option_properties['value'] === $option_properties['default'])
                return false;

            return true;
        });
    }

    /**
     * @return array The list of unused option values
     */
    public function listRemainingOptionsValues()
    {

        $out = [];
        foreach ($this->listRemainingOptions() as $option_definition) {
            $out[ $option_definition['name'] ] = $option_definition['value'];
        }

        return $out;
    }

    /**
     * @return array The list of unused option values
     */
    public function undefinedOptionsValues()
    {
        $out = [];
        foreach ($this->options_mapping as $option_definition) {
            if (isset($option_definition['possibilities']))
                continue;

            if (!isset($option_definition['name']))
                \Debug::dumpJson($option_definition, true);

            $out[ $option_definition['name'] ] = $option_definition['value'];
        }

        return $out;
    }

    /**
     * Uses all the options which are not and return their values.
     *
     * @return array The list of option values
     */
    public function useRemainingOptions()
    {
        $out = [];
        foreach ($this->listRemainingOptions() as $option_name => $option_properties) {
            $out[$option_name] = $this->useOption($option_name);
        }

        return $out;
    }

    /**
     * Checks if an option has the expected value and flags all others as "used"
     * if it's the case. This way, the return statement will not produce
     *
     * This is meant to be used for "return" cases:
     * return $options->finishIfOptionIs('option_name', <option_value>);
     *
     * @param  string $option_name
     * @param  mixed  $expected_value
     * @param  bool   $strict_comparison
     *
     * @return mixed The value
     *
     * @todo  experiment "public function finishIf($test_result, $value_to_return)"
     */
    public function finishIfOptionIs($option_name, $expected_value, $strict_comparison=false)
    {
        $value = $this->getOptionValue($option_name);

        if (   ($strict_comparison && $value === $expected_value)
            || (!$strict_comparison && $value == $expected_value) ) {
            foreach ($this->options_mapping as $option_name => &$option_properties) {
                $option_properties['used'] = true;
            }

            return true;
        }

        return false;
    }

    /**
     * Retrieves the value of an option (or the default one).
     *
     * @param  string $option_name
     *
     * @return mixed The value
     */
    public function getOptionValue($option_name)
    {
        if ( ! isset($this->options_mapping[$option_name])) {
            throw new \InvalidArgumentException(
                "Trying to get avalue of an option which is not supported: '$option_name'"
            );
        }

        if ( ! array_key_exists('value', $this->options_mapping[$option_name]) ) {
            throw new \InvalidArgumentException(
                "No value defined for '$option_name': "
                .var_export($this->options_mapping[$option_name], true)
            );
        }

        return $this->options_mapping[$option_name]['value'];
    }

    /**
     * Retrieves the all the values of options having one.
     *
     * @param  string $option_name
     *
     * @return array  The values0
     */
    public function getAllOptionValues()
    {
        $out = [];
        foreach ($this->options_mapping as $option_name => $properties) {
            if (array_key_exists('value', $properties)) {
                $out[$option_name] = $properties['value'];
            }
        }

        return $out;
    }

    /**
     * Check that all supported options are used at the end of the method
     */
    public function __destruct()
    {
        if ($this->disabled_destruct_checking)
            return;

        $unused_options = array_filter($this->options_mapping, function($option_properties) {
            return ! $option_properties['used'];
        });

        if ($unused_options) {
            // throw new \LogicException(
            // Ideally, a LogicException should be thrown here but this generates
            // incoherences (mainly because it is outside the normal stack)
            header('content-type: text/html');

            trigger_error(
                "Some options have not been used: \n"
                .var_export($unused_options, true)."\n\n"
                .var_export($this->backtrace, true),
                E_USER_ERROR
            );

            // $this->dumpJson(true);
        }
    }

    /**
     */
    public function disableDestructChecking()
    {
        $this->disabled_destruct_checking = true;
    }

    /**
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * ArrayAccess interface
     */
    public function offsetSet($option_name, $option_value)
    {
        return $this->setOptionValue($option_name, $option_value);
    }

    /**
     * ArrayAccess interface
     */
    public function offsetExists($option_name)
    {
        try {
            $this->getOptionValue($option_name);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ArrayAccess interface
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(
            "Not implemented"
        );
    }

    /**
     * ArrayAccess interface. Method called if isset() or empty() is called.
     *
     * /!\ We need the reference here to have the line below working
     *      $this['key']['sub_key'] = 'lalala'
     *
     * @throws \Exception The same as for a classical array with
     *                         "Undefined index" having the good trace end.
     */
    // public function &offsetGet($option_name) // TODO Is there a reason to pass it by ref?
    public function offsetGet($option_name)
    {
        try {
            return $this->getOptionValue($option_name);
        }
        catch (\Exception $e) {
            // here we simply move the Exception location at the one
            // of the caller as the isset() method is called at its
            // location.

            // The true location of the throw is still available through
            // $e->getTrace()
            $trace_location  = $e->getTrace()[1];
            $reflectionClass = new \ReflectionClass( get_class($e) );

            //file
            if (isset($trace_location['file'])) {
                $reflectionProperty = $reflectionClass->getProperty('file');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($e, $trace_location['file']);
            }

            // line
            if (isset($trace_location['line'])) {
                $reflectionProperty = $reflectionClass->getProperty('line');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($e, $trace_location['line']);
            }

            throw $e;
        }
    }

    /**/
}
