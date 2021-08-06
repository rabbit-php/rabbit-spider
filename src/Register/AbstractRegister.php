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
        $i = 0;
        while (true) {
            foreach ($this->servers as $name => $time) {
                if (time() - $time < $this->interval + 3) {
                    $num++;
                }
                if ($name === $this->msg) {
                    $index = $i;
                }
                $i++;
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
