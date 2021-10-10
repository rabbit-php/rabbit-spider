<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Base\Core\Channel;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\Spider\Register\AbstractRegister;
use Swoole\Coroutine;

abstract class IPPoolPlugin extends AbstractProxyPlugin
{
    protected ?string $tunnel = null;
    protected AbstractRegister $regist;

    protected Channel $channel;
    protected int $size = 1000;
    protected int $maxSize = 1000;
    protected int $percent = 90;
    protected int $busySize = 0;
    protected array $runItems = [];
    protected int $cid = 0;
    protected string $workerName;
    protected bool $useLocal = false;

    protected int $retry = 5;

    public function init(): void
    {
        parent::init();
        [
            $this->tunnel,
            $this->size,
            $this->maxSize,
            $this->busySize,
            $this->retry,
            $this->percent,
            $this->useLocal,
        ] = ArrayHelper::getValueByArray(
            $this->config,
            ['tunnel', 'size', 'maxSize', 'busySize', 'retry', 'percent', 'useLocal'],
            [$this->tunnel, $this->size, $this->maxSize, $this->busySize, $this->retry, $this->percent, $this->useLocal]
        );
        $this->busySize === 0 && $this->busySize = (int)ceil($this->maxSize * $this->percent / 100);
        $this->channel = new Channel($this->maxSize);
        $this->regist = getDI('register')->get($this->taskName);
        $this->regist->regist();
        $this->workerName = $this->regist->getMsg();
    }

    public function check(): bool
    {
        if (count($this->runItems) >= $this->maxSize) {
            $this->cid = Coroutine::getCid();
            Coroutine::yield();
        }
        return true;
    }

    public function start(): void
    {
        if (count($this->runItems) < $this->busySize && $this->cid > 0) {
            $cid = $this->cid;
            $this->cid = 0;
            Coroutine::resume($cid);
        }
    }
}
