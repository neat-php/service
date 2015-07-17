<?php namespace Phrodo\Base;

use \Phrodo\Contract\Base\Dispatch as DispatchContract;
use \Phrodo\Contract\Base\Kernel as KernelContract;

/**
 * Kernel class
 */
abstract class Kernel implements KernelContract
{

    /**
     * Container
     *
     * @var DispatchContract
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
     * @param DispatchContract $dispatch
     */
    public function __construct(DispatchContract $dispatch)
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
