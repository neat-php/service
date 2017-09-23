<?php namespace Phrodo\Application\Test;

class ServiceConsumer
{
    /**
     * Service
     *
     * @var Service
     */
    private $service;

    /**
     * ServiceProvider constructor
     *
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Get Service
     *
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Static factory method
     *
     * @return static
     */
    public static function create()
    {
        return new self(new Service);
    }
}