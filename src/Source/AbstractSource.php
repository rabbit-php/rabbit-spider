<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Spider\Manager\ProxyManager;
use Rabbit\Spider\ProxyInterface;
use SplQueue;

abstract class AbstractSource implements ProxyInterface
{
    protected int $loopTime = 10;

    protected int $num = 5;

    protected int $source = -1;

    protected int $size = 200;

    protected bool $release = true;

    protected int $timeout = 5;

    protected array $idle = [];
    protected SplQueue $queue;
    protected array $delIPs = [];
    protected array $waits = [];

    protected ?ProxyManager $manager = null;

    protected int $yield = 0;

    public function __construct()
    {
        $this->queue = new SplQueue();
        $this->resumes = new SplQueue();
    }

    public function setManager(ProxyManager $manager): void
    {
        $this->manager = $manager;
    }

    public function getManager(): ?ProxyManager
    {
        return $this->manager;
    }

    public function getLoopTime(): int
    {
        return $this->loopTime;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function update(string $domain, IP $ip): bool
    {
        $res = false;
        $key = "{$ip->ip}:{$ip->port}";
        if ($ip->release && ($ip->duration <= IP::IP_VCODE || $ip->duration > $ip->timeout * 1000)) {
            if ($ip->duration === IP::IP_VCODE) {
                $this->waits[$domain][] = $ip->toArray();
            } else {
                $this->delIPs[] = $ip->toArray();
            }
            if ($this->idle[$key] ?? false) {
                if (empty($ip->removeHost($domain))) {
                    unset($this->idle[$key]);
                    unset($ip);
                }
            }
            $res = true;
        } elseif ($this->idle[$key] ?? false) {
            $this->queue->enqueue($ip);
        }
        if ($this->yield > 0) {
            \Co::resume($this->yield);
            $this->yield = 0;
        }
        return $res;
    }

    public function getIP(): IP
    {
        while ($this->queue->isEmpty()) {
            $this->yield = \Co::getCid();
            \Co::yield();
        }
        return $this->queue->dequeue();
    }

    public function run(IP $ip): void
    {
        for ($i = 0; $i < $ip->num; $i++) {
            $this->queue->enqueue($ip);
        }
        if ($this->yield !== 0) {
            \Co::resume($this->yield);
            $this->yield = 0;
        }
    }

    abstract public function loadIP(): void;
    abstract protected function flush(): void;
}
