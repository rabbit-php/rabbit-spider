<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

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

    public array $idle = [];
    public array $delIPs = [];
    public array $waits = [];

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

    public function update(string $domain, IP $ip): void
    {
        if ($ip->duration <= 0 || $ip->duration > $ip->timeout * 1000) {
            $key = "{$ip->ip}:{$ip->port}";
            if ($this->idle[$key] ?? false) {
                $this->idle[$key]->removeHost($domain);
                if (empty($this->idle[$key]->getHost())) {
                    unset($this->idle[$key]);
                }
            }

            if ($ip->duration === 0) {
                $this->waits[$domain][] = $ip;
            } else {
                $this->delIPs[] = $ip;
            }
        }
    }

    public function createCtrl(ProxyManager $manager): void
    {
        foreach ($this->hosts as $host => $queue) {
            foreach ($this->idle as $ip) {
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
