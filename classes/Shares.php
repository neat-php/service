<?php

namespace Neat\Service;

interface Shares
{
    /**
     * Get services to share by method name
     *
     * @return array
     */
    public function shares(): array;
}
