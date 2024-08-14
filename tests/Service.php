<?php

namespace Neat\Service\Test;

class Service
{
    /**
     * Method with unknown parameter
     */
    public function unknown($unknown)
    {
    }

    /**
     * Static factory method
     *
     * @return Service
     */
    public static function factory()
    {
        return new static();
    }
}
