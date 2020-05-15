Neat Service components
=======================
[![Stable Version](https://poser.pugx.org/neat/service/version)](https://packagist.org/packages/neat/service)
[![Build Status](https://travis-ci.org/neat-php/service.svg?branch=master)](https://travis-ci.org/neat-php/service)
[![codecov](https://codecov.io/gh/neat-php/service/branch/master/graph/badge.svg)](https://codecov.io/gh/neat-php/service)

Neat service components provide a clean and expressive API for your application
to provide, access and inject services and other dependencies. The [PSR-11](https://www.php-fig.org/psr/psr-11/)
container interface is implemented for optimal interoperability.

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
$container = new Neat\Service\Container();

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
<?php

/** @var Neat\Service\Container $container */

// Suppose we want to access the Neat\Database\Connection service by an alias
$container->alias(PDO::class, 'db');

// Now we can access a service by its db alias
$container->set('db', function() {
    return new PDO('sqlite:memory');
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
<?php

/** @var Neat\Service\Container $container */

class Services
{
    public function now(): DateTime
    {
        return new DateTime('now');
    }

    // Notice how this depends on the service above, the container will
    // automatically resolve this dependency for us.
    public function clock(DateTime $time): Example\Clock
    {
        return new Example\Clock($time);
    }
}

// Now register the service provider
$container->register(new Services());

// To get my clock you would simply use
$container->get(Example\Clock::class);

// Or access the service through its alias (the name of the method)
$container->get('clock');
```

Dependency injection
--------------------
The container can also create objects and call methods for you with a
technique called auto-wiring. This means it will detect, resolve and inject
dependencies automatically based on method signatures and parameter types.
```php
<?php

/** @var Neat\Service\Container $container */

// Assuming your container can produce a PDO and Clock object instance
class BlogController
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPosts(Example\Clock $clock, string $tag = null) {
        // ...
    }
}

// You'd create a controller and automatically inject the PDO object
$blog = $container->create(BlogController::class);

// Call the getPosts method and have it receive the Clock object
$posts = $container->call([$blog, 'getPosts']);

// You can combine these two calls into one invocation
$posts = $container->call('BlogController@getPosts');

// And pass any arguments you wish to specify or override
$sportsPosts = $container->call('BlogController@getPosts', ['tag' => 'sports']);
```
