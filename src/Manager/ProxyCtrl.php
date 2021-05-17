<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use Rabbit\Spider\Exception\EmptyException;
use Rabbit\Spider\Manager\BaseCtrl;
use Rabbit\Spider\Manager\ProxyManager;
use Rabbit\Spider\Source\AbstractSource;
use Rabbit\Spider\Source\IP;
use Rabbit\Spider\SpiderResponse;
use Rabbit\Base\App;
use Rabbit\Base\Core\LoopControl;
use Rabbit\HttpClient\Client;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;
use Swlib\Saber\Request;
use Throwable;

final class ProxyCtrl extends BaseCtrl
{
    private IP $ip;
    private Client $client;
    private array $keyCount = [];
    private string $host;
    private AbstractSource $source;
    private string $key;

    public function __construct(ProxyManager $manager, AbstractSource $source, IP $ip, string $host)
    {
        $this->manager = $manager;
        $this->source = $source;
        $this->host = $host;
        $this->ip = $ip;
        $this->ip->validate();
        $options = [
            'use_pool' => $this->ip->num,
            "target" => true,
            "iconv" => false,
            "redirect" => 0,
            'timeout'  => $this->ip->timeout,
            'headers'  => [
                'DNT' => "1",
                'Accept' => '*/*',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36'
            ]
        ];
        if (!empty($this->ip->proxy)) {
            $options['proxy'] = [
                'http' => "tcp://{$this->ip->proxy}",
                'https' => "tcp://{$this->ip->proxy}",
            ];
        }
        $this->client = new Client($options);
        $this->keyCount[$this->host] = makeChannel($this->ip->num);
        $this->lc = new LoopControl(0);
    }

    public function __destruct()
    {
        Client::release($this->key);
        App::info("{$this->host} source:{$this->ip->source} proxy:{$this->ip->proxy} shutdown!");
    }

    public function getIP(): IP
    {
        return $this->ip;
    }

    public function proxy(string $url, array $options = []): SpiderResponse
    {
        $contents = $this->request($url, $options);
        if ($contents->code === SpiderResponse::CODE_VERCODE) {
            if (($this->ip->checktime ?? false) && $this->lc->sleep !== $this->ip->checktime) {
                $this->lc->sleep = $this->ip->checktime;
                App::warning("use {$this->ip->ip} verification! go to check, loop {$this->ip->checktime}ms");
            }
            return $contents;
        } elseif (($this->ip->checktime ?? false) && $this->lc->sleep === $this->ip->checktime) {
            $this->lc->sleep = 0;
            App::warning("use {$this->ip->ip} restore!");
        }
        if ($contents->code === SpiderResponse::CODE_EMPTY) {
            throw new EmptyException("No body with response");
        } elseif (!$contents->isOK) {
            throw new BadRequestHttpException("got error! code={$contents->code}");
        }
        return $contents;
    }

    public function request(string $url, array $headers = []): SpiderResponse
    {
        $response = new SpiderResponse();
        try {
            $options = [
                'pool_key' => function (Request $request) {
                    return $this->key = Client::getKey($request->getConnectionTarget() + $request->getProxy());
                },
                'headers' => $headers
            ];
            $response->setResponse($this->client->get($url, $options));
        } catch (Throwable $e) {
            $response->code = $e->getCode();
        } finally {
            $this->manager->verification($url, $response);
            if ($response->code === SpiderResponse::CODE_VERCODE) {
                $this->ip->duration = 0;
                $this->ip->release && $this->lc->shutdown();
            } elseif (null !== $response->getResponse()) {
                $this->ip->duration = $response->getResponse()->getDuration();
                if ($this->ip->release && $this->ip->duration > $this->ip->timeout * 1000) {
                    $this->lc->shutdown();
                }
            } else {
                $this->ip->duration = -1;
                $this->ip->release && $this->lc->shutdown();
            }
            if (!$this->keyCount[$this->host]->isEmpty()) {
                $this->keyCount[$this->host]->pop();
            }
            $this->ip->release && $this->source->update($this->host, $this->ip);
            return $response;
        }
    }

    public function loop($queue): void
    {
        if (!$this->isRunning) {
            $this->isRunning = true;
            loop(function () use ($queue) {
                $this->keyCount[$this->host]->push(1);
                $task = $queue->pop();
                rgo(function () use ($task) {
                    $task($this);
                });
            }, 0, $this->lc);
        }
    }
}
