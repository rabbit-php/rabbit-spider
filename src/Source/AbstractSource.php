<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Core\LoopControl;
use Rabbit\Spider\Manager\ProxyCtrl;
use Rabbit\Spider\Manager\ProxyManager;
use Rabbit\Spider\ProxyInterface;

abstract class AbstractSource implements ProxyInterface
{
    protected int $loopTime = 10;

    protected int $num = 5;

    protected int $source = -1;

    protected int $size = 200;

    protected bool $release = true;

    protected array $hosts = [];

    protected int $timeout = 5;

    protected array $idle = [];
    protected array $delIPs = [];
    protected array $waits = [];

    protected ProxyManager $manager;

    public function setManager(ProxyManager $manager): void
    {
        $this->manager = $manager;
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
        $this->createCtrl();
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
            $key = "{$ip->ip}:{$ip->port}";
            if ($this->idle[$key] ?? false) {
                if (empty($ip->removeHost($domain))) {
                    unset($this->idle[$key]);
                    unset($ip);
                }
            }
        }
    }

    protected function run(IP $ip): void
    {
        foreach ($this->hosts as $host => $queue) {
            if ($ip->addHost($host)) {
                $ctrl = new ProxyCtrl($this->manager, $this, $ip, $host);
                $ctrl->loop($queue);
            }
        }
    }

    private function createCtrl(): void
    {
        foreach ($this->hosts as $host => $queue) {
            foreach ($this->idle as $ip) {
                if ($ip->addHost($host)) {
                    $ctrl = new ProxyCtrl($this->manager, $this, $ip, $host);
                    $ctrl->loop($queue);
                }
            }
        }
    }

    abstract public function loadIP(): void;
    abstract protected function flush(): void;
}
