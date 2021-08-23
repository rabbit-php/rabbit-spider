<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use Rabbit\Spider\SpiderResponse;
use Rabbit\Spider\Stores\IProxyStore;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\DB\Expression;
use Rabbit\Spider\Source\IP;
use Throwable;

final class ProxyManager
{
    protected IProxyStore $store;

    public array $attributes = ['ip2long', 'ip', 'port', 'anonymity', 'protocol', 'location', 'score', 'duration', 'source'];

    private array $checkCode = [];

    protected array $queue = [];

    public int $timeout = 10;

    private array $sources = [];
    private bool $running = false;

    public function __construct(IProxyStore $store, array $sources = null)
    {
        $this->store = $store;
        $this->sources = $sources ?? $this->sources;
    }

    public function verification(string $url, SpiderResponse $response): void
    {
        if (key_exists($domain = parse_url($url, PHP_URL_HOST), $this->checkCode)) {
            $response->verification($this->checkCode[$domain]);
        }
    }

    public function getStore(): IProxyStore
    {
        return $this->store;
    }

    public function getQueue(): array
    {
        return $this->queue;
    }

    public function getIP(string $host): IP
    {
        if (!$this->running) {
            $this->running = true;
            foreach ($this->sources as $source) {
                $source->setManager($this);
                loop(function () use ($source) {
                    $source->loadIP();
                }, $source->getLoopTime() * 1000);
            }
        }
        if (!($this->queue[$host] ?? false)) {
            $this->queue[$host] = makeChannel();
            foreach ($this->sources as $source) {
                $source->run();
            }
        }
        return $this->queue[$host]->pop();
    }

    public function save(string $domain, array &$items): void
    {
        $domain = parse_url($domain, PHP_URL_HOST);
        if (!ArrayHelper::isIndexed($items)) {
            $items = [$items];
        }
        $onlyUpdate = false;
        $updateArr = [];
        foreach ($items as $data) {
            if (!isset($data['id'])) {
                if ($data['duration'] > IP::IP_VCODE) {
                    $data['score'] = 100;
                    $updateArr[] = $data;
                }
            } else {
                $onlyUpdate = true;
                if ($data['duration'] === IP::IP_FAILED) {
                    $data['score'] = new Expression('score-1');
                } elseif ($data['duration'] === IP::IP_VCODE) {
                    $data['domain'] = $domain;
                    $data['score'] = 100;
                } else {
                    $data['score'] = 100;
                }
                $updateArr[] = $data;
            }
        }
        if (!empty($updateArr)) {
            $this->store->save($updateArr, $onlyUpdate);
        }
    }

    public function proxy(string $url, string $name = 'local', array $headers = [], int $retry = 5): SpiderResponse
    {
        if (!($this->sources[$name] ?? false)) {
            return null;
        }
        $ctrl = $this->sources[$name];
        $ctrl->setManager($this);
        $ctrl->loadIP(true);
        $idle = $ctrl->getIdle();
        while ($retry--) {
            try {
                $ip = clone $idle[array_rand($idle)];
                $ip->release = false;
                return $ip->proxy($url);
            } catch (Throwable $e) {
                usleep(300 * 1000);
            }
        }
        return throw $e;
    }
}
