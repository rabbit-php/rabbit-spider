<?php

namespace Rabbit\Spider\Exception;

use Rabbit\Base\Core\Exception;

class FailedException extends Exception
{
    public function getName(): string
    {
        return 'Request failed';
    }
}