<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Base\Core\SplChannel;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\Spider\Register\AbstractRegister;
use Swoole\Coroutine;

abstract class IPPoolPlugin extends AbstractProxyPlugin
{
    protected ?string $tunnel = null;
    protected AbstractRegister $regist;

    protected SplChannel $channel;
    protected int $size = 1000;
    protected int $maxSize = 20000;
    protected array $runItems = [];
    protected int $cid = 0;
    protected string $workerName;

    public function init(): void
    {
        parent::init();
        [
            $this->tunnel,
            $this->size,
            $this->maxSize
        ] = ArrayHelper::getValueByArray($this->config, ['tunnel', 'size', 'maxSize'], [$this->tunnel, $this->size, $this->maxSize]);
        $this->channel = makeChannel($this->maxSize);
        $this->regist = getDI('register')->get($this->taskName);
        $this->regist->regist();
        $this->workerName = $this->regist->getMsg();
    }

    public function check(): bool
    {
        if (count($this->runItems) > $this->maxSize) {
            $this->cid = Coroutine::getCid();
            Coroutine::yield();
        }
        return true;
    }

    public function start(): void
    {
        if (count($this->runItems) <= $this->maxSize && $this->cid > 0) {
            $cid = $this->cid;
            $this->cid = 0;
            Coroutine::resume($cid);
        }
    }
}
