<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Model\Model;

class IP extends Model
{
    public ?int $id = null;
    public ?int $ip2long = null;
    public ?string $ip = null;
    public ?int $port = null;
    public ?string $user = null;
    public ?string $pass = null;
    public int $num;
    public bool $release;
    public int $checktime;
    public ?string $proxy = null;
    public int $source = -1;
    public int $timeout = 10;
    public ?int $duration = 1;

    private array $hosts = [];

    public function rules(): array
    {
        return [
            [['proxy'], function () {
                $host = !empty($this->ip) && $this->ip !== '127.0.0.1' ? $this->ip : '';
                $port = !empty($this->port) ? ":{$this->port}" : '';
                $user = !empty($this->user) ? $this->user : '';
                $pass = !empty($this->pass) ? ':' . $this->pass : '';
                $pass = ($user && $pass) ? "$pass@" : '';
                return "$user$pass$host$port";
            }]
        ];
    }

    public function addHost(string $host): bool
    {
        if (!in_array($host, $this->hosts)) {
            $this->hosts[] = $host;
            return true;
        }
        return false;
    }

    public function getHost(): array
    {
        return $this->hosts;
    }

    public function removeHost(string $host): void
    {
        unset($this->hosts[$host]);
    }
}
