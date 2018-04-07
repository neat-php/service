Neat Service components
=======================
[![Stable Version](https://poser.pugx.org/neat/service/version)](https://packagist.org/packages/neat/service)
[![Build Status](https://travis-ci.org/neat-php/service.svg?branch=master)](https://travis-ci.org/neat-php/service)

Neat service components provide a clean and expressive API for your application
to provide and access services.

Getting started
---------------
To install this package, simply issue [composer](https://getcomposer.org) on the
command line:
```
composer require neat/service
```

Service Container
-----------------
The included service container allows you to register and retrieve service
instances using factories and preset instances.
```php
<?php

// First whip up a new container
$container = new Neat\Service\Container;

// Then teach it how to create a service
$container->set(PDO::class, function () {
    return new PDO('sqlite:memory');
});

// And retrieve it using has and get methods
if ($container->has(PDO::class)) {
    $pdo = $container->get(PDO::class);
}
```

Service aliases
---------------
To reference a service you won't always want to use the full class name. Not
just for conveniences sake, but also to decouple your code from its dependency
implementations.
```php
// Suppose we want to access the Neat\Database\Connection service by an alias
$container->alias(Neat\Database\Connection::class, 'db');

// Now we can access a service by its db alias
$container->set('db', function() {
    return new Neat\Database\Connection(...);
});

$db = $container->get('db');
```
You can also use aliases to make a service available by an interface name. This
will come in handy when using dependency injection.


Service providers
-----------------
To help you setup multiple services, you can define a service provider which is
nothing more than an object with public service factory methods.

```php
class Services
{
    public function now(): DateTime
    {
        return new DateTime('now');
    }

    // Notice how this depends on the service above, the container will
    // automatically resolve this dependency for us.
    public function clock(DateTime $time): My\Clock
    {
        return new My\Clock($time)
    }
}

// Now register the service provider
$container->register(new Services);

// To get my clock you would simply use
$container->get(My\Clock::class);

// Or access the service through its alias (the name of the method)
$container->get('clock');
```
