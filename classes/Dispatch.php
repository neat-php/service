<?php namespace Phrodo\Base;

use Phrodo\Contract\Base\Container as ContainerContract;
use Phrodo\Contract\Base\Dispatch as DispatchContract;

/**
 * Dispatch class
 */
class Dispatch implements DispatchContract
{

    /**
     * Container
     *
     * @var ContainerContract
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
     * @param ContainerContract $container Used to fetch arguments (optional)
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container;
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
     * Dispatch to constructor from given class
     *
     * @param string $class
     * @param array  $arguments
     * @return object
     */
    public function construct($class, array $arguments = null)
    {
        $reflection = new \ReflectionClass($class);
        if ($this->detectArguments) {
            $arguments = $this->getArguments($reflection->getConstructor()->getParameters());
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Dispatch to given closure
     *
     * @param callable|string $closure
     * @param array           $arguments
     * @return mixed
     */
    public function to($closure, array $arguments = null)
    {
        $callable = $this->getCallable($closure);
        if ($this->detectArguments) {
            $reflection = $this->getCallableReflection($callable);
            $arguments  = $this->getArguments($reflection->getParameters());
        }

        return call_user_func_array($callable, $arguments);
    }

    /**
     * Dispatch to all given closures
     *
     * @param array $closures
     * @param array $arguments
     * @return array
     */
    public function all(array $closures, array $arguments = null)
    {
        $results = [];
        foreach ($closures as $key => $closure) {
            $results[$key] = $this->to($closure, $arguments);
        }

        return $results;
    }

    /**
     * Get arguments for reflection parameters
     *
     * @param \ReflectionParameter[] $parameters
     * @return array
     */
    protected function getArguments($parameters)
    {
        $arguments = [];
        foreach ($parameters as $parameter) {
            $class = $parameter->getClass()->getName();
            if ($class && $this->container && $this->container->has($class)) {
                $arguments[] = $this->container->get($class);
            } else {
                $arguments[] = null;
            }
        }

        return $arguments;
    }

    /**
     * Get callable
     *
     * Converts to standard array-based callable:
     * "class@method" format
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
        if ($this->object) {
            return [$this->object, $closure];
        }
        if ($this->method) {
            return [$closure, $this->method];
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
        } else {
            return $this->construct($class);
        }
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
