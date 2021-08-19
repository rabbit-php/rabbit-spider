<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Spider\Source\IP;

abstract class ISpiderTask
{
    protected string $domain;

    abstract public function __invoke(IP $ip): void;

    public function getDomain(): string
    {
        return $this->domain;
    }
}
