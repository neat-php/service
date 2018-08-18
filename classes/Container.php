<?php

namespace Neat\Service;

use Psr\Container\ContainerInterface;

/**
 * Service Container class
 */
class Container implements ContainerInterface
{
    /**
     * Instances
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * Factories
     *
     * @var callable[]
     */
    protected $factories = [];

    /**
     * Shared classes/factories
     *
     * @var bool[]
     */
    protected $shared = [];

    /**
     * Aliases
     *
     * @var string[]
     */
    protected $classes = [];

    /**
     * Injector
     *
     * @var Injector
     */
    protected $injector;

    /**
     * Constructor
     *
     * @param Injector $injector
     */
    public function __construct(Injector $injector = null)
    {
        $this->injector = ($injector ?? new Injector)->withContainer($this);
    }

    /**
     * Get the injector
     *
     * @return Injector
     */
    public function injector()
    {
        return $this->injector;
    }

    /**
     * Can the container produce an instance for this service?
     *
     * The service can either be a class name, interface name or other alias
     *
     * @param string $service
     * @return bool
     */
    public function has($service)
    {
        $class = $this->resolve($service);

        return isset($this->instances[$class])
            || isset($this->factories[$class]);
    }

    /**
     * Get or create a service instance
     *
     * The service can either be an interface name, class name or alias
     *
     * @param string $service
     * @return object
     * @throws NotFoundException
     */
    public function get($service)
    {
        $class = $this->resolve($service);

        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (isset($this->factories[$class])) {
            $instance = $this->injector->call($this->factories[$class]);
            if ($this->shared[$class] ?? false) {
                $this->instances[$class] = $instance;
            }

            return $instance;
        }

        throw new NotFoundException('Could not find service ' . $class);
    }

    /**
     * Set a service instance, factory or class name
     *
     * @param string                 $service
     * @param object|callable|string $concrete
     */
    public function set($service, $concrete)
    {
        $class = $this->resolve($service);

        if (is_callable($concrete)) {
            $this->factories[$class] = $concrete;
            $this->instances[$class] = null;
        } else {
            $this->factories[$class] = null;
            $this->instances[$class] = $concrete;
        }
    }

    /**
     * Share a service instance
     *
     * @param string $service
     */
    public function share($service)
    {
        $class = $this->resolve($service);

        $this->shared[$class] = true;
    }

    /**
     * Register services from a provider
     *
     * @param object $provider
     * @throws \ReflectionException
     */
    public function register($provider)
    {
        $reflection = new \ReflectionClass($provider);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }

            $return = $method->getReturnType();
            if (!$return || $return->isBuiltin()) {
                continue;
            }

            $class = (string) $return;

            $this->classes[$method->name] = $class;
            $this->factories[$class]      = $method->getClosure($provider);
        }
    }

    /**
     * Alias a class name
     *
     * The service can be a custom name, interface or abstract/parent
     * class name you want to associate with the given class.
     *
     * @param string $service
     * @param string $class
     */
    public function alias($service, $class)
    {
        $this->classes[$service] = $class;
    }

    /**
     * Resolve a service to its class name
     *
     * @param string $service
     * @return string
     */
    public function resolve($service)
    {
        return $this->classes[$service] ?? $service;
    }
}
