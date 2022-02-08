<?php

namespace Neat\Service;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
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
     * Extensions
     *
     * @var callable[]
     */
    protected $extensions = [];

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
     * @param string $id Class name, interface name or other alias
     * @return bool Only true when the service was explicitly set
     */
    public function has(string $id): bool
    {
        $class = $this->resolve($id);

        return isset($this->instances[$class])
            || isset($this->factories[$class]);
    }

    /**
     * Get service instance
     *
     * @param string $id Class name, interface name or other alias
     * @return mixed
     * @throws NotFoundException when the service was not explicitly set
     */
    public function get(string $id)
    {
        $class = $this->resolve($id);

        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (isset($this->factories[$class])) {
            $instance = $this->call($this->factories[$class]);
            foreach ($this->extensions[$class] ?? [] as $extension) {
                $instance = $extension($instance);
            }
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
     * @return mixed
     * @throws NotFoundException
     */
    public function getOrCreate(string $service)
    {
        $class = $this->resolve($service);

        if ($this->has($class)) {
            return $this->get($class);
        }

        $instance = $this->create($class);
        if ($this->shared[$class] ?? false) {
            $this->instances[$class] = $instance;
        }

        return $instance;
    }

    /**
     * Get service factory
     *
     * @param string $service Class name, interface name or other alias
     * @return callable
     */
    public function factory(string $service): callable
    {
        return function () use ($service) {
            return $this->get($service);
        };
    }

    /**
     * Set a service instance, factory or class name
     *
     * @param string $service
     * @param mixed  $concrete
     */
    public function set(string $service, $concrete): void
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
     * Extend a service
     *
     * @param string          $service
     * @param callable|string $extension
     * @param string          $parameter
     */
    public function extend(string $service, $extension, string $parameter = 'service'): void
    {
        $class = $this->resolve($service);

        $this->extensions[$class][] = function ($instance) use ($extension, $parameter) {
            return $this->call($extension, [$parameter => $instance]);
        };

        if ($instance = $this->instances[$class] ?? null) {
            $this->shared[$class]    = true;
            $this->instances[$class] = null;
            $this->factories[$class] = function () use ($instance, $extension, $parameter) {
                return $instance;
            };
        }
    }

    /**
     * Share a service instance
     *
     * @param string $service
     */
    public function share(string $service): void
    {
        $class = $this->resolve($service);

        $this->shared[$class] = true;
    }

    /**
     * Register services from a provider
     *
     * @param object $provider
     */
    public function register(object $provider): void
    {
        $reflection = new ReflectionClass($provider);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }

            $return = $method->getReturnType();
            if (!$return || $return->isBuiltin()) {
                continue;
            }

            $class = $return->getName();

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
    protected function registerAliases(Aliases $provider): void
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
    protected function registerShares(Shares $provider): void
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
    public function alias(string $service, string $class): void
    {
        $this->classes[$service] = $class;
    }

    /**
     * Resolve a service to its class name
     *
     * @param string $service
     * @return string
     */
    public function resolve(string $service): string
    {
        return $this->classes[$service] ?? $service;
    }

    /**
     * Call the given closure, method or function
     *
     * @param callable|string $closure
     * @param array           $named
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
    public function create(string $class, array $named = []): object
    {
        $reflection = $this->getConstructorReflection($class);
        $arguments  = $reflection ? $this->getArguments($reflection, $named) : [];

        $instance = new $class(...$arguments);
        foreach ($this->extensions[$class] ?? [] as $extension) {
            $instance = $extension($instance);
        }

        return $instance;
    }

    /**
     * Get arguments for reflected function or method
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array                      $named
     * @return array
     * @throws NotFoundException
     */
    protected function getArguments(ReflectionFunctionAbstract $reflection, array $named = []): array
    {
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $class = null;
            if ($parameter->getType()) {
                $class = $parameter->getType()->getName();
            }
            if (array_key_exists($parameter->name, $named)) {
                $arguments[] = $named[$parameter->name];
            } elseif ($class && $this->has($class)) {
                $arguments[] = $this->get($class);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($class) {
                $arguments[] = $this->getOrCreate($class);
            } elseif ($parameter->isVariadic()) {
                break;
            } elseif ($parameter->isOptional()) {
                $arguments[] = null;
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
     * @return array|callable|string
     * @throws NotFoundException
     */
    protected function getCallable($closure)
    {
        if (!is_string($closure)) {
            return $closure;
        }
        if (strpos($closure, '@') !== false) {
            [$class, $method] = explode('@', $closure);

            return [$this->getOrCreate($class), $method];
        }
        if (strpos($closure, '::') !== false) {
            [$class, $method] = explode('::', $closure);

            return [$class, $method];
        }
        if (class_exists($closure) && method_exists($closure, '__invoke')) {
            return $this->getOrCreate($closure);
        }

        return $closure;
    }

    /**
     * Get callable reflection
     *
     * @param array|callable|object|string $callable
     * @return ReflectionFunctionAbstract
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
    protected function getConstructorReflection(string $class): ?ReflectionMethod
    {
        try {
            $reflection = new ReflectionClass($class);

            return $reflection->getConstructor();
        } catch (ReflectionException $e) {
            throw NotFoundException::forException($e);
        }
    }
}
