<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Spider\Source\IP;

interface ProxyInterface
{
    public function update(string $domain, IP $ip): void;
}
