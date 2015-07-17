<?php namespace Phrodo\Base;

use Phrodo\Contract\Base\Container as ContainerContract;
use Phrodo\Contract\Base\Dispatch as DispatchContract;

/**
 * Service Container class
 */
class Container implements ContainerContract
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
     * Dispatcher
     *
     * @var DispatchContract
     */
    protected $dispatch;

    /**
     * Constructor
     *
     * @param DispatchContract $dispatch
     */
    public function __construct(DispatchContract $dispatch = null)
    {
        $this->dispatch = $dispatch ?: new Dispatch($this);
        $this->dispatch->withObject($this);

        $this->instances[ContainerContract::class] = $this;
    }

    /**
     * Does the container hold an instance or factory for a service?
     *
     * The service can either be a class name, interface name or other alias
     *
     * @param string $service
     * @return bool
     */
    public function has($service)
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
    public function get($service)
    {
        $abstract = $this->resolve($service);
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $instance = null;
        if (isset($this->classes[$abstract])) {
            $instance = $this->dispatch->construct($this->classes[$abstract]);
        } elseif (isset($this->factories[$abstract])) {
            $instance = $this->dispatch->to($this->factories[$abstract]);
        } elseif (class_exists($abstract)) {
            $instance = $this->dispatch->construct($abstract);
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
    public function set($service, $concrete, $shared = false)
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
    public function alias($alias, $abstract)
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve an alias to its abstract name
     *
     * @param string $alias
     * @return string
     */
    public function resolve($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }

        return $alias;
    }

}
