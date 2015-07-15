<?php namespace Phrodo\Base;

/**
 * Service Container class
 */
class Container implements \Phrodo\Contract\Base\Container
{

    /**
     * Instances
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * Classes
     *
     * @var array
     */
    protected $classes = [];

    /**
     * Factories
     *
     * @var callable[]
     */
    protected $factories = [];

    /**
     * Shared classes/factories
     *
     * @var array
     */
    protected $shared = [];

    /**
     * Aliases
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Create a new class instance
     *
     * Arguments will be resolved automatically
     *
     * @param string $class
     * @return object
     */
    function create($class)
    {
        $reflection = new \ReflectionClass($class);
        $arguments = $this->getArguments($reflection->getConstructor()->getParameters());

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Call a closure
     *
     * Arguments will be resolved automatically
     *
     * @param callable|string $closure
     * @param object          $defaultObject
     * @param string          $defaultMethod
     * @return mixed
     */
    function call($closure, $defaultObject = null, $defaultMethod = null)
    {
        $callable   = $this->getCallable($closure, $defaultObject, $defaultMethod);
        $reflection = $this->getCallableReflection($callable);
        $arguments  = $this->getArguments($reflection->getParameters());

        return call_user_func_array($callable, $arguments);
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
            if (!$class || !$this->has($class)) {
                $arguments[] = null;
            } else {
                $arguments[] = $this->get($class);
            }
        }

        return $arguments;
    }

    /**
     * Get callable
     *
     * Converts "Class@method" notation to a valid callable
     *
     * @param callable|string $closure
     * @param object          $defaultObject
     * @param string          $defaultMethod
     * @return callable
     */
    protected function getCallable($closure, $defaultObject = null, $defaultMethod = null)
    {
        if (is_string($closure) && strpos($closure, '@') !== false) {
            list($object, $method) = explode('@', $closure);
            if ($object) {
                $object = $this->get($object);
            } else {
                $object = $defaultObject ?: $this;
            }
            if (!$method) {
                $method = $defaultMethod ?: '__invoke';
            }
            return [$object, $method];
        }

        return $closure;
    }

    /**
     * Get callable reflection
     *
     * @param callable $callable
     * @return \ReflectionFunction|\ReflectionMethod
     */
    protected function getCallableReflection($callable)
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }
        if (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }

        return new \ReflectionFunction($callable);
    }

    /**
     * Does the container hold an instance or factory for a service?
     *
     * The service can either be a class name, interface name or other alias
     *
     * @param string $service
     * @return bool
     */
    function has($service)
    {
        $abstract = $this->resolve($service);

        return isset($this->instances[$abstract])
            || isset($this->factories[$abstract])
            || isset($this->classes[$abstract]);
    }

    /**
     * Get or create a service instance
     *
     * The service can either be an interface name, class name or alias
     *
     * @param string $service
     * @return object
     */
    function get($service)
    {
        $abstract = $this->resolve($service);
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $instance = null;
        if (isset($this->classes[$abstract])) {
            $instance = $this->create($this->classes[$abstract]);
        } elseif (isset($this->factories[$abstract])) {
            $instance = $this->call($this->factories[$abstract]);
        } elseif (class_exists($abstract)) {
            $instance = $this->create($abstract);
        }

        if (isset($this->shared[$abstract]) && $this->shared[$abstract]) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Set a service instance, factory or class name
     *
     * Objects with __invoke method are regarded as instances themselves even
     * though they are callable. If you intend to use such an object as
     * factory, wrap it into a closure that invokes the object.
     *
     * @param string                 $service
     * @param object|callable|string $concrete
     * @param bool                   $shared
     */
    function set($service, $concrete, $shared = false)
    {
        $abstract = $this->resolve($service);

        if (is_object($concrete)) {
            $this->instances[$abstract] = $concrete;
        } elseif (is_string($concrete)) {
            $this->classes[$abstract] = $concrete;
        } else {
            $this->factories[$abstract] = $concrete;
        }

        $this->shared[$abstract] = $shared;
    }

    /**
     * Alias an abstract class or interface
     *
     * @param string $alias
     * @param string $abstract
     */
    function alias($alias, $abstract)
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve an alias to its abstract name
     *
     * @param string $alias
     * @return string
     */
    function resolve($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }

        return $alias;
    }

}
