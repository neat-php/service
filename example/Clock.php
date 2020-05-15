<?php

namespace Example;

use DateTime;

class Clock
{
    /** @var DateTime */
    private $time;

    /**
     * Clock constructor
     *
     * @param DateTime $time
     */
    public function __construct(DateTime $time)
    {
        $this->time = $time;
    }
}
