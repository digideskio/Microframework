<?php
return function () {
    /**
     * Used to store functions and allow recursive callbacks.
     * @var null|callback
     */
    static $deploy = null;
    /**
     * Defined matches.
     * @var array
     */
    static $ms = array();
    /**
     * Dependency Injection callbacks, used for settings too.
     * @var array
     */
    static $di = array();

    // used to shorten code
    $num_args = func_num_args();
    $get_args = func_get_args();

    // used to retrieve currently defined matches
    // http://www.php.net/manual/en/regexp.reference.conditional.php
    // http://stackoverflow.com/questions/14598972/catch-all-regular-expression
    if ($num_args === 0 && PHP_SAPI !== 'cli') {
        return '/?(?!(' . implode('|', $ms) . ')$).*';
    }
    // functions used for Dependency Injection and settings
    elseif ($num_args === 2 && preg_match("#^[^/].+#", $get_args[0])) {
        return $di[$get_args[0]] = $get_args[1];
    } elseif ($num_args === 1 && preg_match("#^[^/].+#", $get_args[0])) {
        return is_callable($di[$get_args[0]])
            ? call_user_func($di[$get_args[0]])
            : $di[$get_args[0]];
    }

    // functions have to be stored only once
    if (is_null($deploy) && PHP_SAPI === 'cli') {
        /**
         * Command line interface for the main function.
         *
         * @link http://php.net/manual/en/language.types.float.php
         *
         * @param  callback $callback Function invoked when script ends
         * @param  integer  $priority Set `$callback` priority from 0 (high) to ~1.8e308 (low)
         * @return void
         */

        $deploy = function ($callback, $priority = 0) use (&$deploy) {
            /**
             * Checking well formed call
             */
            if (!is_callable($callback)) {
                throw new BadFunctionCallException(
                    'Argument 1 passed to function must be callable, '
                    . gettype($callback) . ' given'
                );
            } elseif (!is_numeric($priority)) {
                throw new BadFunctionCallException(
                    'Argument 2 passed to function must be numeric, '
                    . gettype($priority) . ' given'
                );
            }

            /**
             * Arguments passed to the script.
             * @link http://php.net/manual/en/reserved.variables.argv.php
             * @var array
             */
            $argv = $GLOBALS['argv'];

            if ($priority > 0) {
                /**
                 * Recursion is used to set callback priority
                 */
                register_shutdown_function($deploy, $callback, $priority - 1);
            } else {
                $argv[0] = $callback;
                /**
                 * register_shutdown_function is used to call added functions when script ends
                 * @link http://it2.php.net/manual/en/function.register-shutdown-function.php
                 */
                call_user_func_array('register_shutdown_function', $argv);
            }
        };
    } elseif (is_null($deploy)) {
        /**
         * Function used as a router.
         *
         * @link http://php.net/manual/en/language.types.float.php
         *
         * @param  string   $regex    Regular expression used to match requested URL
         * @param  callback $callback Function invoked when there's a match
         * @param  string   $method   Request method(s)
         * @param  float    $priority Set `$callback` priority from 0 (high) to ~1.8e308 (low)
         * @return void
         */

        $deploy = function ($regex, $callback, $method = 'GET', $priority = 0) use (&$deploy, &$ms) {
            /**
             * Checking well formed call
             */
            if (!is_string($regex)) {
                throw new BadFunctionCallException(
                    'Argument 1 passed to function must be string, '
                    . gettype($regex) . ' given'
                );
            } elseif (!is_callable($callback)) {
                throw new BadFunctionCallException(
                    'Argument 2 passed to function must be callable, '
                    . gettype($callback) . ' given'
                );
            } elseif (!is_string($method)) {
                throw new BadFunctionCallException(
                    'Argument 3 passed to function must be string, '
                    . gettype($method) . ' given'
                );
            } elseif (!is_numeric($priority)) {
                throw new BadFunctionCallException(
                    'Argument 4 passed to function must be numeric, '
                    . gettype($priority) . ' given'
                );
            }

            // match stored as unique
            $ms[md5($regex)] = $regex;

            if ($priority > 0) {
                /**
                 * Recursion is used to set callback priority
                 */
                register_shutdown_function($deploy, $regex, $callback, $method, $priority - 1);
            } elseif (preg_match('#' . $method . '#', $_SERVER['REQUEST_METHOD'])) {
                if (preg_match('#^' . $regex . '$#', $_SERVER['REQUEST_URI'], $matches)) {
                    /**
                     * Named subpatterns aren't allowed
                     * @link http://it2.php.net/manual/en/regexp.reference.subpatterns.php
                     */
                    while (list($key) = each($matches)) {
                        if (!is_int($key)) {
                            unset($matches[$key]);
                        }
                    }
                    /**
                     * Closure is added to `register_shutdown_function` calling
                     */
                    if (isset($matches[0]) && $matches[0] === $_SERVER['REQUEST_URI']) {
                        $matches[0] = $callback;
                    } else {
                        array_unshift($matches, $callback);
                    }
                    /**
                     * register_shutdown_function is used to call added functions when script ends
                     * @link http://it2.php.net/manual/en/function.register-shutdown-function.php
                     */
                    call_user_func_array('register_shutdown_function', $matches);
                }
            }
        };
    }

    return call_user_func_array($deploy, func_get_args());
};
