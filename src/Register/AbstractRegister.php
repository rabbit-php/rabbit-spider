<?php

declare(strict_types=1);

namespace Rabbit\Spider\Register;

abstract class AbstractRegister
{
    protected string $name;
    protected bool $status = false;
    protected int $interval = 60;
    protected array $servers = [];
    protected bool $isRegist = false;
    protected string $msg;
    protected int $tick = 10;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->msg = current(swoole_get_local_ip()) . ':' . getmypid();
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    public function getWorker(): array
    {
        $num = 0;
        while (true) {
            $servers = $this->getServers();
            ksort($servers);
            $del = [];
            foreach ($servers as $name => $time) {
                if ($name === $this->msg) {
                    $index = $num;
                }
                if (time() < (int)$time + $this->interval) {
                    $num++;
                } else {
                    $del[] = $name;
                }
            }
            $del && $this->delServers($del);
            if (isset($index) && $num > 0) {
                break;
            }
            sleep(3);
        }
        return [$num, $index];
    }

    abstract public function regist(): void;
    abstract public function getServers(): array;
    abstract public function delServers(array $servers): void;
}
