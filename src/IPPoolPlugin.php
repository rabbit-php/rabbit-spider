<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\Spider\Register\AbstractRegister;

abstract class IPPoolPlugin extends AbstractProxyPlugin
{
    protected ?string $tunnel = null;
    protected AbstractRegister $regist;

    public function init(): void
    {
        parent::init();
        $this->tunnel = ArrayHelper::getValue($this->config, 'tunnel');
        $this->regist = getDI('register')->get($this->taskName);
        $this->regist->regist();
    }
}
