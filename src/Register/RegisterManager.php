<?php

declare(strict_types=1);

namespace Rabbit\Spider\Register;

class RegisterManager
{
    protected array $regists = [];
    protected string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
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
