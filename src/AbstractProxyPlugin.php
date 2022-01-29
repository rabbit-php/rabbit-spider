<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Data\Pipeline\AbstractPlugin;
use Rabbit\Spider\Manager\ProxyManager;

abstract class AbstractProxyPlugin extends AbstractPlugin
{
    public ProxyManager $manager;

    public function init(): void
    {
        parent::init();
        $this->manager = service('proxy.manager');
    }
}
