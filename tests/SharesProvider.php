<?php

namespace Neat\Service\Test;

use Neat\Service\Shares;

class SharesProvider implements Shares
{
    /**
     * Get services to share by method name
     *
     * @return array
     */
    public function shares(): array
    {
        return ['service'];
    }

    /**
     * Provide service
     *
     * @return Service
     */
    public function service(): Service
    {
        return new Service();
    }
}
