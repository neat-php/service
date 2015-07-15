<?php namespace Phrodo\Base;

use \Phrodo\Contract\Base\Container as ContainerContract;
use \Phrodo\Contract\Base\Kernel as KernelContract;

/**
 * Kernel class
 */
abstract class Kernel implements KernelContract
{

    /**
     * Container
     *
     * @var ContainerContract
     */
    protected $container;

    /**
     * Bootstrappers
     *
     * @var array
     */
    protected $bootstrappers = [];

    /**
     * Terminators
     *
     * @var array
     */
    protected $terminators = [];

    /**
     * Constructor
     *
     * @param ContainerContract $container
     */
    public function __construct(ContainerContract $container)
    {
        $this->container = $container;
    }

    /**
     * Bootstrap the application
     */
    public function bootstrap()
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->container->call($bootstrapper, $this);
        }
    }

    /**
     * Handles application core tasks
     */
    public abstract function handle();

    /**
     * Terminate the application
     */
    public function terminate()
    {
        foreach ($this->terminators as $terminator) {
            $this->container->call($terminator, $this);
        }
    }

}
