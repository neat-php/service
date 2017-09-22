<?php namespace Phrodo\Application;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Traversable;

/**
 * Dispatcher class
 */
class Dispatcher
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
     * Default object
     *
     * @var object
     */
    protected $object;

    /**
     * Default method
     *
     * @var string
     */
    protected $method = '__invoke';

    /**
     * Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Constructor
     *
     * @param Container $container Used to fetch arguments (optional)
     */
    public function __construct(Container $container = null)
    {
        if ($container) {
            $this->containers[] = $container;
        }
    }

    /**
     * Call the given closure, method or function
     *
     * @param callable $closure
     * @return mixed
     */
    public function call($closure)
    {
        $callable  = $this->getCallable($closure);
        $arguments = $this->getArguments($this->getCallableReflection($callable));

        return $callable(...$arguments);
    }

    /**
     * Create an object by calling the given classes' constructor
     *
     * @param string $class
     * @return object
     */
    public function create($class)
    {
        $reflection  = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $arguments   = $constructor ? $this->getArguments($constructor) : [];

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
     * Resolve using supplied arguments
     *
     * @param array|Traversable $arguments
     * @return $this
     */
    public function withArguments($arguments)
    {
        if ($arguments instanceof Traversable) {
            $arguments = iterator_to_array($arguments);
        }

        $clone = clone $this;
        $clone->arguments = array_merge($this->arguments, $arguments);

        return $clone;
    }

    /**
     * Dispatch with default namespace
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
     * Dispatch with default object
     *
     * @param object $object
     * @return $this
     */
    public function withObject($object)
    {
        $clone = clone $this;
        $clone->object = $object;

        return $clone;
    }

    /**
     * Dispatch with default method
     *
     * @param string $method
     * @return $this
     */
    public function withMethod($method)
    {
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Get arguments for reflected function or method
     *
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @return array
     * @throws NotFoundException
     */
    protected function getArguments($reflection)
    {
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            if ($class = $parameter->getClass()) {
                foreach ($this->containers as $container) {
                    if ($container->has($class->name)) {
                        $arguments[] = $container->get($class->name);
                        continue 2;
                    }
                }
            }
            if (array_key_exists($parameter->name, $this->arguments)) {
                $arguments[] = $this->arguments[$parameter->name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                if ($reflection instanceof ReflectionMethod) {
                    $method = $reflection->class . '::' . $reflection->name;
                } else {
                    $method = $reflection->name;
                }
                throw new NotFoundException('Could not resolve parameter $' . $parameter->getName() . ' for ' . $method);
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
     * "method" format (with the default object)
     * "class" format (with the default method)
     *
     * @param callable|string $closure
     * @return callable
     */
    protected function getCallable($closure)
    {
        if (!is_string($closure)) {
            return $closure;
        }
        if (strpos($closure, '@') !== false) {
            list($class, $method) = explode('@', $closure);

            return [$this->getObject($class), $method];
        }
        if (strpos($closure, '::') !== false) {
            return explode('::', $closure);
        }
        if ($this->object && method_exists($this->object, $closure)) {
            return [$this->object, $closure];
        }
        if ($this->method) {
            return [$this->getObject($closure), $this->method];
        }

        return $closure;
    }

    /**
     * Get object by class
     *
     * @param string $class
     * @return object
     */
    protected function getObject($class)
    {
        if ($this->namespace && strpos($class, '\\') === false) {
            $class = $this->namespace . '\\' . $class;
        }

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
     */
    protected function getCallableReflection($callable)
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        return new ReflectionFunction($callable);
    }
}
