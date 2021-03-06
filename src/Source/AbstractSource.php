<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Spider\Manager\ProxyManager;

abstract class AbstractSource
{
    protected int $loopTime = 10;

    protected int $num = 5;

    protected int $source = -1;

    protected int $size = 200;

    protected bool $release = true;

    protected int $timeout = 5;

    protected array $idle = [];
    protected array $delIPs = [];

    protected ?ProxyManager $manager = null;

    public function setManager(ProxyManager $manager): void
    {
        $this->manager ??= $manager;
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

    public function getIdle(): array
    {
        return $this->idle;
    }

    public function release(string $host, IP $ip): bool
    {
        if ($ip->isLocal) {
            $this->manager->getLocalQueue()[$host]?->enqueue($ip);
            return false;
        }
        if ($ip->remove) {
            return true;
        }
        if ($ip->source >= 0 && $ip->duration <= IP::IP_VCODE) {
            $ip->remove = true;
            $this->delIPs[] = $ip->toArray();
            unset($this->idle["{$ip->ip}:{$ip->port}"]);
            return true;
        }
        $ip->release && $this->manager->getQueue()[$host]?->enqueue($ip);
        return false;
    }

    public function run(): void
    {
        foreach ($this->manager->getQueue() as $host => $queue) {
            $ips = [];
            foreach ($this->idle as $ip) {
                if ($ip->addHost($host)) {
                    $ips[] = [$ip, $ip->num];
                }
            }
            while ($ips) {
                foreach ($ips as $i => &$item) {
                    $queue->enqueue($item[0]);
                    $item[0]->source < 0 && $this->manager->getLocalQueue()[$host]?->enqueue($item[0]);
                    $item[1]--;
                    if ($item[1] === 0) {
                        unset($ips[$i]);
                    }
                }
            }
        }
    }

    protected function flush(): void
    {
        $this->delIPs = [];
    }

    abstract public function loadIP(): void;
}
