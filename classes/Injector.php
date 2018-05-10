<?php

namespace Neat\Service;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Injector class
 */
class Injector
{
    /**
     * Containers
     *
     * @var Container[]
     */
    protected $containers = [];

    /**
     * Default namespace
     *
     * @var string
     */
    protected $namespace;

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
        $class      = $this->getClass($class);
        $reflection = $this->getConstructorReflection($class);
        $arguments  = $reflection ? $this->getArguments($reflection, $named) : [];

        return new $class(...$arguments);
    }

    /**
     * Resolve arguments from container
     *
     * @param Container $container
     * @param bool      $prioritize
     * @return $this
     */
    public function withContainer(Container $container, $prioritize = false)
    {
        $clone = clone $this;

        if ($prioritize) {
            array_unshift($clone->containers, $container);
        } else {
            array_push($clone->containers, $container);
        }

        return $clone;
    }

    /**
     * Resolve class names from namespace
     *
     * @param string $namespace
     * @return $this
     */
    public function withNamespace($namespace)
    {
        $clone = clone $this;
        $clone->namespace = trim($namespace, '\\');

        return $clone;
    }

    /**
     * Get arguments for reflected function or method
     *
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @param array                               $named
     * @return array
     * @throws NotFoundException
     */
    protected function getArguments($reflection, array $named = [])
    {
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            if (array_key_exists($parameter->name, $named)) {
                $arguments[] = $named[$parameter->name];
            } elseif ($class = $parameter->getClass()) {
                $arguments[] = $this->getObject($class->name);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
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

            return [$this->getObject($this->getClass($class)), $method];
        }
        if (strpos($closure, '::') !== false) {
            list($class, $method) = explode('::', $closure);

            return [$this->getClass($class), $method];
        }

        return $closure;
    }

    /**
     * Get a class with namespace
     *
     * @param string $class
     * @return string
     */
    protected function getClass($class)
    {
        if ($this->namespace && strpos($class, '\\') === false) {
            return $this->namespace . '\\' . $class;
        }

        return $class;
    }

    /**
     * Get object by class
     *
     * @param string $class
     * @return object
     * @throws NotFoundException
     */
    protected function getObject($class)
    {
        foreach ($this->containers as $container) {
            if ($container->has($class)) {
                return $container->get($class);
            }
        }

        return $this->create($class);
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
