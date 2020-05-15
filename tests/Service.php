<?php

namespace Neat\Service\Test;

class Service
{
    /**
     * Method with unknown parameter
     *
     * @param mixed $unknown
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
