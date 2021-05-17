<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use App\Tasks\Spider\Exception\EmptyException;
use App\Tasks\Spider\SpiderResponse;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\HttpClient\Client;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;
use Throwable;

final class LocalCtrl extends BaseCtrl
{
    private ?string $ip = null;
    private int $size = 5;
    private Client $client;

    public function __construct(ProxyManager $manager, string $ip = null)
    {
        $this->manager = $manager;
        if ($ip !== null) {
            $parsed_url = parse_url($ip);
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
            $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
            $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
            $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
            $pass = ($user || $pass) ? "$pass@" : '';
            $this->ip = "tcp://$user$pass$host$port";
            $this->size = isset($parsed_url['path']) ? (int)str_replace('/', '', $parsed_url['path']) : 1;
        }

        $options = [
            'use_pool' => $this->size,
            "target" => true,
            "iconv" => false,
            "redirect" => 0,
            'headers'  => [
                'DNT' => "1",
                'Accept' => '*/*',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36'
            ]
        ];

        if ($this->ip !== null) {
            $options['proxy'] = [
                'http' => $this->ip,
                'https' => $this->ip,
            ];
        }
        $this->client = new Client($options);
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
                'headers' => $headers,
                'timeout'  => $this->manager->timeout,
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
