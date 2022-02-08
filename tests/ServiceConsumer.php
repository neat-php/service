<?php

namespace Neat\Service\Test;

class ServiceConsumer
{
    /**
     * Service
     *
     * @var Service|null
     */
    private $service;

    /**
     * ServiceProvider constructor
     *
     * @param Service|null $service
     */
    public function __construct(Service $service = null)
    {
        $this->service = $service;
    }

    /**
     * Get Service
     *
     * @return Service|null
     */
    public function getService(): ?Service
    {
        return $this->service;
    }

    /**
     * Static factory method
     *
     * @return static
     */
    public static function create(): ServiceConsumer
    {
        return new self(new Service());
    }
}
