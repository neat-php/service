<?php namespace Phrodo\Application;

/**
 * Service Container class
 */
class Container
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
     * Dispatcher
     *
     * @var Dispatcher
     */
    protected $dispatch;

    /**
     * Constructor
     *
     * @param Dispatcher $dispatch
     */
    public function __construct(Dispatcher $dispatch = null)
    {
        $this->dispatch = ($dispatch ?? new Dispatcher)->withContainer($this);

        $this->initialize();
    }

    /**
     * Invoke the given closure
     *
     * @param callable $closure
     * @return object
     */
    public function __invoke($closure)
    {
        return $this->dispatch->call($closure);
    }

    /**
     * Get the dispatcher
     *
     * @return Dispatcher
     */
    public function dispatch()
    {
        return $this->dispatch;
    }

    /**
     * Initialize the container
     */
    protected function initialize()
    {
        $this->classes['container']     = static::class;
        $this->classes[self::class]     = static::class;
        $this->instances[static::class] = $this;
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
            || isset($this->factories[$class])
            || class_exists($class);
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

        $instance = null;
        if (isset($this->factories[$class])) {
            $instance = $this->dispatch->call($this->factories[$class]);
        } elseif (class_exists($class)) {
            $instance = $this->dispatch->create($class);
        } else {
            throw new NotFoundException('Could not find service '. $class);
        }

        if ($this->shared[$class] ?? false) {
            $this->instances[$class] = $instance;
        }

        return $instance;
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
        } else {
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
     */
    public function provide($provider)
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
