Phrodo Application
==================
Phrodo Application service components provide a clean and expressive API for
your application services.

Getting started
---------------
To install this package, simply issue [composer](https://getcomposer.org) on the
command line:
```
composer require phrodo/application
```

Service Container
-----------------
The included service container allows you to register and retrieve service
instances using factories and preset instances.
```php
<?php

// First whip up a new container
$container = new Phrodo\Application\Container;

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
// Suppose we want to access the Phrodo\Database\Connection service by an alias
$container->alias(Phrodo\Database\Connection::class, 'db');

// Now we can access a service by its db alias
$container->set('db', function() {
    return new Phrodo\Database\Connection(...);
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
Configuration
-------------
To assist with configuration, the included Configuration class provides an
easy key-value store for configuration parameters. To load your configuration
data, simply use the set or merge methods or pass the configuration data
into the constructor.

```php
$configuration = new Configuration;
$configuration->set('app_title', 'My app');
$configuration->merge(parse_ini_file('.env'));

$title = $configuration->get('app_title', 'Default title');
```

Todo
----
- Documentation
- Repository
- Event manager
