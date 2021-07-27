<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use Rabbit\Spider\Exception\EmptyException;
use Rabbit\Spider\SpiderResponse;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\HttpClient\Client;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;
use Rabbit\Spider\Source\IP;
use Swlib\Saber\Request;
use Throwable;

final class LocalCtrl extends BaseCtrl
{
    private IP $ip;
    private Client $client;

    public function __construct(IP $ip)
    {
        $this->ip = $ip;
        $this->ip->validate();

        $options = [
            'use_pool' => false,
            "target" => true,
            "iconv" => false,
            "redirect" => 0,
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
    }

    public function setManager(ProxyManager $manager): void
    {
        $this->manager = $manager;
    }

    public function proxy(string $url, array $options = []): SpiderResponse
    {
        $contents = $this->request($url, $options);
        if ($contents->code === SpiderResponse::CODE_VERCODE) {
            return $contents;
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
                    return Client::getKey($request->getConnectionTarget() + $request->getProxy());
                },
                'headers' => $headers,
                'timeout'  => $this->ip->timeout,
            ];
            $response->setResponse($this->client->get($url, $options));
        } catch (Throwable $e) {
            $response->code = $e->getCode();
        } finally {
            $this->manager->verification($url, $response);
            return $response;
        }
    }

    public function loop($queue): void
    {
        throw new NotSupportedException("LocalCtrl not support loop!");
    }
}
