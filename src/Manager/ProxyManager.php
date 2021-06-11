<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use Rabbit\Spider\SpiderResponse;
use Rabbit\Spider\Stores\IProxyStore;
use Rabbit\Base\Exception\InvalidArgumentException;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\DB\Expression;
use Rabbit\Spider\Source\IP;

final class ProxyManager
{
    private bool $isRunning = false;
    private ?array $tc = null;

    protected IProxyStore $store;

    public array $attributes = ['ip2long', 'ip', 'port', 'anonymity', 'protocol', 'location', 'score', 'duration', 'source'];

    private array $checkCode = [];

    protected $queue;

    public int $timeout = 10;

    private array $hosts = [];

    private array $sources = [];

    public function __construct(IProxyStore $store, array $sources = null, string $tc = null)
    {
        $this->store = $store;
        $this->sources = $sources ?? $this->sources;
        if ($tc !== null) {
            $tcArr = explode(',', $tc);
            foreach ($tcArr as $val) {
                $parsed = parse_url($val);
                parse_str($parsed['query'] ?? [], $query);
                $ctrl = new TunnelCtrl($this, $val, isset($query['run']) ? (bool)$query['run'] : false);
                $this->tc[$parsed['scheme']] = $ctrl;
            }
        }
    }


    public function getQueue()
    {
        if ($this->queue === null) {
            $this->queue = makeChannel();
        }
        return $this->queue;
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

    public function getTunnels(): array
    {
        return $this->tc ?? [];
    }

    public function getTunnel(string $name): BaseCtrl
    {
        if ($this->tc[$name] ?? false) {
            return $this->tc[$name];
        }
        throw new InvalidArgumentException("No $name tunnel!");
    }

    public function start(): void
    {
        if (!$this->isRunning) {
            $this->isRunning = true;

            if ($this->tc) {
                foreach ($this->tc as $tunnel) {
                    $tunnel->loop($this->getQueue());
                }
            }

            foreach ($this->sources as $source) {
                loop(function () use ($source) {
                    $source->loadIP($this);
                    $source->createCtrl($this);
                }, $source->getLoopTime() * 1000);
            }

            loop(function () {
                $task = $this->queue->pop();
                $host = $task->getDomain();
                if (!array_key_exists($host, $this->hosts)) {
                    $queue = makeChannel();
                    foreach ($this->sources as $source) {
                        $source->addHost($host, $queue);
                        $source->createCtrl($this);
                    }
                    $this->hosts[$host] = $queue;
                } else {
                    $queue = $this->hosts[$host];
                }
                if (!$queue->push($task, $this->timeout)) {
                    $this->queue->push($task);
                }
            }, 0);
        }
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
}
