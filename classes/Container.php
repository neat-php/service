<?php

namespace Neat\Service;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

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
     * Has service instance or factory?
     *
     * @param string $service Class name, interface name or other alias
     * @return bool Only true when the service was explicitly set
     */
    public function has($service)
    {
        $class = $this->resolve($service);

        return isset($this->instances[$class])
            || isset($this->factories[$class]);
    }

    /**
     * Get service instance
     *
     * @param string $service Class name, interface name or other alias
     * @return object
     * @throws NotFoundException when the service was not explicitly set
     */
    public function get($service)
    {
        $class = $this->resolve($service);

        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (isset($this->factories[$class])) {
            $instance = $this->call($this->factories[$class]);
            if ($this->shared[$class] ?? false) {
                $this->instances[$class] = $instance;
            }

            return $instance;
        }

        throw new NotFoundException('Could not find service ' . $class);
    }

    /**
     * Get or create service instance
     *
     * @param string $service Class name, interface name or other alias
     * @return object
     * @throws NotFoundException
     */
    public function getOrCreate($service)
    {
        $class = $this->resolve($service);

        if ($this->has($class)) {
            return $this->get($class);
        }

        return $this->create($class);
    }

    /**
     * Get service factory
     *
     * @param string $service Class name, interface name or other alias
     * @return callable
     */
    public function factory($service): callable
    {
        return function () use ($service) {
            return $this->get($service);
        };
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

            // TODO Just use $return->getName() when PHP 7.0 support is dropped.
            $class = method_exists($return, 'getName') ? $return->getName() : (string) $return;

            $this->classes[$method->name] = $class;
            $this->factories[$class]      = $method->getClosure($provider);
        }

        if ($provider instanceof Aliases) {
            $this->registerAliases($provider);
        }
        if ($provider instanceof Shares) {
            $this->registerShares($provider);
        }
    }

    /**
     * Register aliases
     *
     * @param Aliases $provider
     */
    protected function registerAliases(Aliases $provider)
    {
        foreach ($provider->aliases() as $service => $class) {
            $this->alias($service, $class);
        }
    }

    /**
     * Register shares
     *
     * @param Shares $provider
     */
    protected function registerShares(Shares $provider)
    {
        foreach ($provider->shares() as $service) {
            $this->share($service);
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

    /**
     * Call the given closure, method or function
     *
     * @param callable $closure
     * @param array    $named
     * @return mixed
     * @throws NotFoundException
     */
    public function call($closure, array $named = [])
    {
        $callable   = $this->getCallable($closure);
        $reflection = $this->getCallableReflection($callable);
        $arguments  = $this->getArguments($reflection, $named);

        return $callable(...$arguments);
    }

    /**
     * Create an object of the given class
     *
     * @param string $class
     * @param array  $named
     * @return object
     * @throws NotFoundException
     */
    public function create($class, array $named = [])
    {
        $reflection = $this->getConstructorReflection($class);
        $arguments  = $reflection ? $this->getArguments($reflection, $named) : [];

        return new $class(...$arguments);
    }

    /**
     * Get arguments for reflected function or method
     *
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @param array                               $named
     * @return array
     * @throws NotFoundException
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function getArguments($reflection, array $named = [])
    {
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $class = $parameter->getClass();
            if (array_key_exists($parameter->name, $named)) {
                $arguments[] = $named[$parameter->name];
            } elseif ($class && $this->has($class->name)) {
                $arguments[] = $this->get($class->name);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($class) {
                $arguments[] = $this->getOrCreate($class->name);
            } elseif ($parameter->isVariadic()) {
                break;
            } else {
                throw NotFoundException::forParameter($parameter, $reflection);
            }
        }

        return $arguments;
    }

    /**
     * Get callable
     *
     * Converts to standard array-based callable:
     * "class@method" format
     * "class::method" format
     *
     * @param callable|string $closure
     * @return callable
     * @throws NotFoundException
     */
    protected function getCallable($closure)
    {
        if (!is_string($closure)) {
            return $closure;
        }
        if (strpos($closure, '@') !== false) {
            list($class, $method) = explode('@', $closure);

            return [$this->getOrCreate($class), $method];
        }
        if (strpos($closure, '::') !== false) {
            list($class, $method) = explode('::', $closure);

            return [$class, $method];
        }

        return $closure;
    }

    /**
     * Get callable reflection
     *
     * @param callable $callable
     * @return ReflectionFunction|ReflectionMethod
     * @throws NotFoundException
     */
    protected function getCallableReflection($callable)
    {
        try {
            if (is_array($callable)) {
                return new ReflectionMethod($callable[0], $callable[1]);
            }
            if (is_object($callable) && !$callable instanceof Closure) {
                return new ReflectionMethod($callable, '__invoke');
            }

            return new ReflectionFunction($callable);
        } catch (ReflectionException $e) {
            throw NotFoundException::forException($e);
        }
    }

    /**
     * Get constructor reflection
     *
     * @param string $class
     * @return ReflectionMethod|null
     * @throws NotFoundException
     */
    protected function getConstructorReflection($class)
    {
        try {
            $reflection = new ReflectionClass($class);

            return $reflection->getConstructor();
        } catch (ReflectionException $e) {
            throw NotFoundException::forException($e);
        }
    }
}
