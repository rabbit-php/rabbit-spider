<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use App\Tasks\Spider\Manager\ProxyManager;
use Rabbit\Data\Pipeline\AbstractPlugin;

abstract class AbstractProxyPlugin extends AbstractPlugin
{
    public ProxyManager $manager;

    public function init(): void
    {
        parent::init();
        $this->manager = getDI('proxy.manager');
    }
}
