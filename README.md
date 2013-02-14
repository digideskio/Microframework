# Microframework
This is a PHP microframework based on [anonymous functions](http://php.net/manual/en/functions.anonymous.php).
## Features
 * requested URLs matched using regular expressions
 * request methods (matches using regular expressions too)
 * differenced [FIFO](http://en.wikipedia.org/wiki/FIFO) [queues](http://en.wikipedia.org/wiki/Queue_%28abstract_data_type%29) for each `$priority`
 * command line usage
 * backward compatibility
 * integrated [Dependency Injection](http://en.wikipedia.org/wiki/Dependency_injection) system
 * settings system

## CGI Usage

Hello World
```php
<?php
$mf = require_once('microframework.php');

$mf('/', function () {
    echo "Hello World!";
});
?>
```

second argument must be [callable](http://php.net/manual/en/language.types.callable.php), so you can use
```php
<?php
$mf('/', function () { /* ... */ });
$mf('/', 'function');
$mf('/', array($Object, 'method'));
$mf('/', array('Class', 'staticMethod'));
$mf('/', $Object); // Used with __invoke
?>
```

HTTP request methods are the third parameter and are controlled using a regular expression.
```php
<?php
$mf('/', function () {
    echo "Hello " . $_POST['name'] . "!";
}, 'POST');
?>
```

```php
<?php
$mf('/', function () {
    echo "Hello " . $_SERVER['REQUEST_METHOD'] . "!";
}, 'GET|POST');
?>
```

Routes are defined using a regular expression too.
```php
<?php
$mf('/hello/world', function () {
    echo "Hello ";
});
$mf('/hello(.*)', function () {
    echo "World!";
});
// Output: Hello World!
?>
```

## CLI Usage
Hello World
```php
<?php
$mf = require_once('microframework.php');

// Usage: $ php test.php

$mf(function () {
    echo "Hello World!";
});
?>
```
Like CGI usage, first argument must be [callable](http://php.net/manual/en/language.types.callable.php).
Function arguments are `$argv` [arguments](http://php.net/manual/en/reserved.variables.argv.php)
```php
<?php
// Usage: $ php test.php foo bar

$mf(function ($a, $b) {
    echo $b . ' ' . $a;
});

// Output: bar foo
?>
```

## Priority
Priority is used set callbacks order, each function is called using highest priority 0 but you can set it passing a not negative integer or a not negative float number.

```php
<?php
// $mf('/', function () { echo 'A'; });
$mf('/', function () { echo 'A'; }, 'GET', 0);
$mf('/', function () { echo 'B'; }, 'GET', 1);

// Output: AB
?>
```

```php
<?php
$mf('/', function () { echo 'A'; }, 'GET', 1);
$mf('/', function () { echo 'B'; }, 'GET', 0);

// Output: BA
?>
```

In command line usage priority is the second parameter.

To break callbacks chain use the [`exit()`](http://it2.php.net/manual/en/function.exit.php) function.

## Backward compatibility
```php
<?php
// Source code on 2012-01-01
$mf('/([a-zA-Z]+)', function ($name) {
  echo "Hi " . $name;
}, 'GET|POST');
?>
```

If after some time this microframework will change adding the possibility to set the method as an array
```php
<?php
// Source code of 2013-01-01 (current)
$mf('/([a-zA-Z]+)', function ($name) {
    echo "Hello " . $name;
}, array('GET', 'POST'));
?>
```

old things can be saved changing variable names
```php
<?php
// Source code of 2013-01-01 (current)
$mf('/([a-zA-Z]+)', function ($name) {
    echo "Hello " . $name;
}, array('GET', 'POST'));

// Source code of 2012-01-01
$mf_old('/([a-zA-Z]+)', function ($name) {
    echo "Hi " . $name;
}, 'GET|POST');
?>
```

This is used for a fast replacement of old `$mf` functions in your codebase by replacing the variable,
when you've done and started relaxing you can update your old source.
```php
<?php
// Source code of 2013-01-01 (current)
$mf('/([a-zA-Z]+)', function ($name) {
    echo "Hello " . $name;
}, array('GET', 'POST'));

// Source code of 2012-01-01
// updated on 2013-01-02 to current version
$mf('/([a-zA-Z]+)', function ($name) {
    echo "Hi " . $name;
}, array('GET', 'POST'));
?>
```

Backward compatibility is not guaranteed forever, of course.

## 404
The 404 error is defined as a regular expression too and is matched if no one of **currently** defined regular expressions match current URL.
To retrieve the 404 regular expression call the function without arguments (available only if not in CLI mode).
```php
<?php
$mf('/', function () {
    echo "Hello World!";
});

$mf($mf(), function () {
    echo "Error 404: Page not Found";
});
?>
```

## Dependency Injection

Some days ago [Faryshta Mextly](https://plus.google.com/u/0/101549844949845796518/posts/dSE5pU3E4Y5) told me that this isn't a microframework, it's only a routing system, and he was right, so 
I started thinking which services should a framework have and final solution was a 
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) system that could be used for every other service.

It works like the routing system, except for the first parameter that must not start with `/`.
Second parameter should be an [`anonymous function`](http://it2.php.net/manual/en/functions.anonymous.php), but can be a [`callable`](http://php.net/manual/en/language.types.callable.php).

```php
<?php
class Bar
{
    public $foo = 5;
}

$mf('bar', function () {
    return new Bar;
});

$mf('/', function () use ($mf) {
    var_dump($mf('bar') -> foo);
});

// Output: 5
?>
```

## Settings
Settings work like dependency injection, but you can't pass anything [`callable`](http://php.net/manual/en/language.types.callable.php).
```php
<?php
$mf('pi', M_PI);

$mf('/', function () use ($mf) {
    var_dump($mf('pi'));
});

// Output: 3.1415926535898
?>
```

## Tip
It's suggested to create the unused PHP [`main`](http://it2.php.net/manual/en/function.main.php) function.

```php
<?php
if (!function_exists('main')) {
    function main() {
        static $mf = null;
        if (is_null($mf))
            $mf = require_once('microframework.php');
        return call_user_func_array($mf, func_get_args());
    }
}

main('/', function () {
    echo "Hello World!";
});
?>
```
