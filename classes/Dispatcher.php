<?php namespace Phrodo\Application;

/**
 * Dispatcher class
 */
class Dispatcher
{
    /**
     * Container
     *
     * @var Container
     */
    protected $container;

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
     * Detect arguments?
     *
     * @var bool
     */
    protected $detectArguments = false;

    /**
     * Constructor
     *
     * @param Container $container Used to fetch arguments (optional)
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Call the given closure
     *
     * @param callable $closure
     * @return object
     */
    public function call($closure)
    {
        $callable = $this->getCallable($closure);
        if ($this->detectArguments) {
            $arguments = $this->getArguments($this->getCallableReflection($callable));
        } else {
            $arguments = [];
        }

        return call_user_func_array($callable, $arguments);
    }

    /**
     * Make an object by calling the given classes' constructor
     *
     * @param string $class
     * @return object
     */
    public function make($class)
    {
        $reflection = new \ReflectionClass($class);
        if ($this->detectArguments && $constructor = $reflection->getConstructor()) {
            $arguments = $this->getArguments($constructor);
        } else {
            $arguments = [];
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Dispatch with default namespace
     *
     * @param string $namespace
     * @return $this
     */
    public function withNamespace($namespace)
    {
        $this->namespace = trim($namespace, '\\');

        return $this;
    }

    /**
     * Dispatch with default object
     *
     * @param object $object
     * @return $this
     */
    public function withObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Dispatch with default method
     *
     * @param string $method
     * @return $this
     */
    public function withMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Enable argument detection
     *
     * @return $this
     */
    public function withArgumentDetection()
    {
        $this->detectArguments = true;

        return $this;
    }

    /**
     * Get arguments for reflected function or method
     *
     * @param \ReflectionFunctionAbstract $reflection
     * @return array
     */
    protected function getArguments($reflection)
    {
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            if ($this->container && $parameter->getClass()) {
                $arguments[] = $this->container->get($parameter->getClass()->name);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
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

        if ($this->container) {
            return $this->container->get($class);
        }

        return $this->make($class);
    }

    /**
     * Get callable reflection
     *
     * @param callable $callable
     * @return \ReflectionFunction|\ReflectionMethod
     */
    protected function getCallableReflection($callable)
    {
        if (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }

        return new \ReflectionFunction($callable);
    }
}