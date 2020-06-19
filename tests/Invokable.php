<?php

namespace Neat\Service\Test;

class Invokable
{
    public function __invoke()
    {
        return 'test';
    }
}
