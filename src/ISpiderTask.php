<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Spider\Manager\BaseCtrl;

abstract class ISpiderTask
{
    protected string $domain;

    abstract public function __invoke(BaseCtrl $ctrl): void;

    public function getDomain(): string
    {
        return $this->domain;
    }
}
