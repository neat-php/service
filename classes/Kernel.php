<?php namespace Phrodo\Application;

/**
 * Kernel class
 */
abstract class Kernel
{
    /**
     * Dispatcher
     *
     * @var Dispatcher
     */
    protected $dispatch;

    /**
     * Bootstrappers
     *
     * @var array
     */
    protected $bootstrappers = [];

    /**
     * Handlers
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Exception handlers
     *
     * @var array
     */
    protected $exceptionHandlers = [];

    /**
     * Terminators
     *
     * @var array
     */
    protected $terminators = [];

    /**
     * Constructor
     *
     * @param Dispatcher $dispatch
     */
    public function __construct(Dispatcher $dispatch)
    {
        $this->dispatch = $dispatch->withObject($this)->withArgumentDetection();
    }

    /**
     * Bootstrap the application
     */
    public function bootstrap()
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->dispatch->call($bootstrapper);
        }
    }

    /**
     * Handle application core tasks
     */
    public function handle()
    {
        foreach ($this->handlers as $handler) {
            if ($this->dispatch->call($handler)) {
                return;
            }
        }

        throw new \RuntimeException('Unable to handle the request');
    }

    /**
     * Handle exceptions
     *
     * @todo use dispatcher to call exception handlers
     * @param \Exception|\Throwable $e
     */
    public function exception($e)
    {
        foreach ($this->exceptionHandlers as $exceptionHandler) {
            $exceptionHandler($e);
        }
    }

    /**
     * Terminate the application
     */
    public function terminate()
    {
        foreach ($this->terminators as $terminator) {
            $this->dispatch->call($terminator);
        }
    }

    /**
     * Run the application (shortcut for bootstrap, handle then terminate)
     *
     * @codeCoverageIgnore Because one catch block is unreachable in PHP 5 or 7
     */
    public function run()
    {
        $this->bootstrap();

        try {
            $this->handle();
        } catch (\Throwable $e) {
            $this->exception($e);
        } catch (\Exception $e) {
            $this->exception($e);
        }

        $this->terminate();
    }
}
