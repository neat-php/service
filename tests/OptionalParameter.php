<?php

namespace Neat\Service\Test;

use stdClass;

class OptionalParameter
{
    /** @var stdClass|null */
    private $service;

    public function __construct(?stdClass $service)
    {
        $this->service = $service;
    }

    public function getService(): ?stdClass
    {
        return $this->service;
    }
}
