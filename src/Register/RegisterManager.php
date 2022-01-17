<?php

declare(strict_types=1);

namespace Rabbit\Spider\Register;

class RegisterManager
{
    protected array $regists = [];

    public function __construct(protected string $class)
    {
    }

    public function get(string $name, string $msg = null): AbstractRegister
    {
        if (!($this->regists[$name] ?? false)) {
            $class = $this->class;
            $this->regists[$name] = new $class($name, $msg);
        }
        return $this->regists[$name];
    }
}
