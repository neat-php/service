<?php

namespace Neat\Service\Test;

use PDO;

class ServiceProvider
{
    /**
     * Provide db service instance
     *
     * @return Service
     */
    public function db(): Service
    {
        return new Service;
    }

    /**
     * Return boolean (not a service object at all)
     *
     * @return bool
     */
    public function boolean(): bool
    {
        return true;
    }

    /**
     * Return something unspecified
     *
     * @return mixed
     */
    public function unspecified()
    {
        return new class
        {
        };
    }

    /**
     * Inaccessible protected service factory
     *
     * @return PDO
     */
    protected function inaccessible(): PDO
    {
        return new PDO('sqlite::memory:');
    }

    /**
     * Inappropriate static service factory
     *
     * @return PDO
     */
    public static function inappropriate(): PDO
    {
        return new PDO('sqlite::memory:');
    }
}
