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
        $this->msg = current(swoole_get_local_ip()) . ':' . getmygid();
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
        $index = 0;
        while (true) {
            foreach ($this->servers as $name => $time) {
                if ($name === $this->msg) {
                    $index = $num;
                }
                if (time() < $time) {
                    $num++;
                }
            }
            if ($num > 0) {
                break;
            }
            sleep(3);
        }
        return [$num, $index];
    }

    abstract public function regist(): void;
}
