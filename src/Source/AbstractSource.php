<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Spider\Manager\ProxyManager;
use Rabbit\Spider\ProxyInterface;

abstract class AbstractSource implements ProxyInterface
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

    public function update(string $host, IP $ip): bool
    {
        $key = "{$ip->ip}:{$ip->port}";
        if ($ip->source >= 0 && ($ip->duration <= IP::IP_VCODE || $ip->duration > $ip->timeout * 1000)) {
            if ($ip->duration === IP::IP_VCODE) {
                $this->waits[$host][] = $ip->toArray();
            } else {
                $this->delIPs[] = $ip->toArray();
            }
            unset($this->idle[$key]);
            return true;
        }
        return false;
    }

    public function run(): void
    {
        foreach ($this->idle as $ip) {
            $ip->loop();
        }
    }

    abstract public function loadIP(): void;
    abstract protected function flush(): void;
}
