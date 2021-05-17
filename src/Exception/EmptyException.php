<?php

declare(strict_types=1);

namespace Rabbit\Spider\Exception;

use Rabbit\Base\Core\Exception;

class EmptyException extends Exception
{
    public function getName(): string
    {
        return 'Empty response body';
    }
}
