<?php

namespace Neat\Service\Test;

use Neat\Service\Aliases;

class AliasesProvider implements Aliases
{
    /**
     * Get services to share by method name
     *
     * @return array
     */
    public function aliases(): array
    {
        return [
            'svc' => Service::class,
        ];
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
