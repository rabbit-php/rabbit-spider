<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Generator;

interface IInnerProxy
{
    public function getProxys(): array|Generator;
}
