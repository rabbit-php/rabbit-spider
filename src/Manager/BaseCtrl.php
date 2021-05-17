<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use Rabbit\Spider\Exception\EmptyException;
use Rabbit\Spider\SpiderResponse;
use Rabbit\Base\Core\LoopControl;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;

abstract class BaseCtrl
{
    protected ?LoopControl $lc = null;
    protected bool $isRunning = false;
    protected bool $autoRun;
    protected ProxyManager $manager;

    public function __construct(bool $autoRun)
    {
        $this->autoRun = $autoRun;
    }

    public function getLC(): ?LoopControl
    {
        return $this->lc;
    }

    public function proxy(string $url, array $headers = []): SpiderResponse
    {
        $contents = $this->request($url, $headers);
        if ($contents->code === SpiderResponse::CODE_VERCODE) {
            return $contents;
        } elseif ($contents->code === SpiderResponse::CODE_EMPTY) {
            throw new EmptyException("No body with response");
        } elseif (!$contents->isOK) {
            throw new BadRequestHttpException("got error! code={$contents->code}");
        }
        return $contents;
    }

    abstract function loop($queue): void;
    abstract function request(string $url, array $headers = []): SpiderResponse;
}
