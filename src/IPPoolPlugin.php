<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Base\Helper\ArrayHelper;

abstract class IPPoolPlugin extends AbstractProxyPlugin
{
    protected ?string $tunnel = null;

    public function init(): void
    {
        parent::init();
        $this->tunnel = ArrayHelper::getValue($this->config, 'tunnel');
    }
}
