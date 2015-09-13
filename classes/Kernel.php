<?php namespace Phrodo\Application;

use Some\Application\Dispatcher as DispatcherContract;
use Some\Application\Kernel as KernelContract;

/**
 * Kernel class
 */
abstract class Kernel implements KernelContract
{

    /**
     * Dispatcher
     *
     * @var DispatcherContract
     */
    protected $dispatch;

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
     * @param DispatcherContract $dispatch
     */
    public function __construct(DispatcherContract $dispatch)
    {
        $this->dispatch = $dispatch;
        $this->dispatch->withObject($this);
    }

    /**
     * Bootstrap the application
     */
    public function bootstrap()
    {
        $this->dispatch->all($this->bootstrappers);
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
        $this->dispatch->all($this->terminators);
    }

}
