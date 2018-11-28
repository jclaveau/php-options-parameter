<?php
namespace JClaveau;

/**
 */
class DebugBacktrace
{
    /**
     * @param  array $options limit|ignore_args|ignore_while
     * @return array
     */
    public static function getBacktrace($options=[])
    {
        if ( empty($options['limit']) ) {
            $limit = null;
        }
        else {
            $limit = $options['limit'];
        }

        $flags = 0;
        if ( ! empty($options['provide_object']) ) {
            $flags |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
        }

        if ( empty($options['ignore_args']) ) {
            $flags |= DEBUG_BACKTRACE_IGNORE_ARGS;
        }

        if ( empty($options['ignore_while']) ) {
            $ignore_while = function ($backtrace_call, $backtrace_call_index) {
                // print_r($backtrace_call);
                return isset($backtrace_call['class']) && $backtrace_call['class'] === __CLASS__;
            };
        }
        else {
            $ignore_while = $options['ignore_while'];
        }

        $out = [];
        foreach (debug_backtrace($flags, null) as $backtrace_call_index => $backtrace_call) {
            if ($ignore_while( $backtrace_call, $backtrace_call_index ))
                continue;

            $backtrace_call['file'] = isset($backtrace_call['file']) ? $backtrace_call['file'] : null;
            $backtrace_call['line'] = isset($backtrace_call['line']) ? $backtrace_call['line'] : null;

            $backtrace_call['file'] = static::relativePath($backtrace_call['file']);

            if (isset($backtrace_call['class'])) {
                $backtrace_call['call'] =
                    $backtrace_call['class'] . $backtrace_call['type'] . $backtrace_call['function'] . '()';
            }
            elseif (isset($backtrace_call['function'])) {
                $backtrace_call['call'] = $backtrace_call['function'] . '()';
            }
            else {
                $backtrace_call['call'] = '\Closure';
            }

            $out[] = $backtrace_call;

            if ( ! --$limit )
                break;
        }

        return array_reverse($out);
    }

    /**
     *
     * @param  array $options limit|ignore_args|ignore_while
     * @return array|null
     */
    final public static function getCaller($options=[])
    {
        $options['limit'] = 2;
        $backtrace = static::getBacktrace( $options );

        return $backtrace[0];
    }

    /**
     * @param  string $path
     * @return string
     */
    protected static function relativePath($path)
    {
        if ( ! $path)
            return $path;

        $path = realpath($path);

        // as a lib inside vendor
        if (preg_match('#^(.+)/([^/]+/vendor/.+)$#', $path, $matches)) {
            return $matches[2];
        }

        // as a class of the current lib
        if (preg_match('#^(.+)/([^/]+/src/.+)$#', $path, $matches)) {
            return $matches[2];
        }

        return $path;
    }

    /**/
}
