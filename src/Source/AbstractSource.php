<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Core\LoopControl;
use Rabbit\Spider\Manager\ProxyCtrl;
use Rabbit\Spider\Manager\ProxyManager;
use Rabbit\Spider\ProxyInterface;
use WeakMap;

abstract class AbstractSource implements ProxyInterface
{
    protected int $loopTime = 10;

    protected int $num = 5;

    protected int $source = -1;

    protected int $size = 200;

    protected bool $release = true;

    protected array $hosts = [];

    protected int $timeout = 5;

    protected WeakMap $idle;
    protected array $delIPs = [];
    protected array $waits = [];

    public function __construct()
    {
        $this->idle = new WeakMap();
    }

    public function getLoopTime(): int
    {
        return $this->loopTime;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function addHost(string $host, $queue): bool
    {
        if ($this->hosts[$host] ?? false) {
            return false;
        }
        $this->hosts[$host] = $queue;
        return true;
    }

    public function getHost(): array
    {
        return $this->hosts;
    }

    public function update(string $domain, IP $ip, LoopControl $lc): void
    {
        if ($ip->duration <= IP::IP_VCODE || $ip->duration > $ip->timeout * 1000) {
            $lc->shutdown();
            if ($ip->duration === IP::IP_VCODE) {
                $this->waits[$domain][] = $ip->toArray();
            } else {
                $this->delIPs[] = $ip->toArray();
            }
            if ($this->idle->offsetExists($ip)) {
                if (empty($ip->removeHost($domain))) {
                    unset($ip);
                }
            }
        }
    }

    public function createCtrl(ProxyManager $manager): void
    {
        foreach ($this->hosts as $host => $queue) {
            foreach ($this->idle as $ip => $key) {
                if ($ip->addHost($host)) {
                    $ctrl = new ProxyCtrl($manager, $this, $ip, $host);
                    $ctrl->loop($queue);
                }
            }
        }
    }

    abstract public function loadIP(ProxyManager $manager): void;
    abstract protected function flush(ProxyManager $manager): void;
}
