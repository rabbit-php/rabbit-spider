<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy;

use App\Tasks\Spider\AbstractProxyPlugin;
use Rabbit\Base\Exception\InvalidConfigException;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Data\Pipeline\Message;
use Rabbit\DB\Exception;
use Rabbit\DB\StaleObjectException;
use ReflectionException;
use Throwable;

/**
 * Class SaveIPS
 * @package Spider\Proxy
 */
class SaveIPS extends AbstractProxyPlugin
{
    /**
     * @param Message $msg
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws ReflectionException
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function run(Message $msg): void
    {
        if (!$msg->data) {
            return;
        }
        $tableName = $msg->opt['table'];
        $this->manager->save($tableName, $msg->data);
    }
}
