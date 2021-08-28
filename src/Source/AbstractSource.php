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
    protected array $waits = [];

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
        $key = "{$ip->ip}:{$ip->port}";
        if ($ip->source >= 0 && $ip->duration <= IP::IP_VCODE) {
            if ($ip->duration === IP::IP_VCODE) {
                $this->waits[$host][] = $ip->toArray();
            } else {
                $this->delIPs[] = $ip->toArray();
            }
            unset($this->idle[$key]);
            if (count($this->idle) === 0) {
                $this->idle = [];
            }
            return true;
        } elseif ($ip->release && ($this->idle[$key] ?? false)) {
            $this->manager->getQueue()[$host]->enqueue($ip);
        }
        return false;
    }

    public function run(): void
    {
        if ($this->source >= 0) {
            $this->idle = array_slice($this->idle, 0, null, true);
        }
        foreach ($this->manager->getQueue() as $host => $queue) {
            foreach ($this->idle as $ip) {
                for ($i = 0; $i < $ip->num; $i++) {
                    if ($ip->addHost($host)) {
                        $queue->enqueue($ip);
                    }
                }
            }
        }
    }

    abstract public function loadIP(): void;
    abstract protected function flush(): void;
}
