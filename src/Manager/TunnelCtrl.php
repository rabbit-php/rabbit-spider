<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use App\Tasks\Spider\Exception\EmptyException;
use App\Tasks\Spider\SpiderResponse;
use Rabbit\Base\Core\LoopControl;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\HttpClient\Client;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;
use Throwable;

class TunnelCtrl extends BaseCtrl
{
    private string $ip;
    private int $num;
    private int $limit;

    protected ProxyManager $manager;

    protected Client $client;

    public function __construct(ProxyManager $manager, string $tunnel, bool $autoRun = false)
    {
        $this->manager = $manager;
        $this->autoRun = $autoRun;
        $parsed = parse_url($tunnel);
        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user = isset($parsed['user']) ? $parsed['user'] : '';
        $pass = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $this->ip = "tcp://$user$pass$host$port";
        $query = [];
        isset($parsed['query']) && parse_str($parsed['query'], $query);
        [$num, $limit] = ArrayHelper::getValueByArray($query, ['num', 'limit'], [5, 1]);
        $this->num = (int)$num;
        $this->limit = (int)$limit;
        $this->client = new Client([
            "use_pool" => true,
            "target" => true,
            "iconv" => false,
            "redirect" => 0,
            "proxy"   => [
                "http"  => $this->ip,
                "https"  => $this->ip,
            ],
            'timeout' => $this->manager->timeout,
            'headers' => [
                'DNT' => "1",
                'Accept' => '*/*',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36'
            ]
        ]);
    }

    public function getLC(): LoopControl
    {
        return $this->lc;
    }

    public function proxy(string $url, array $options = []): SpiderResponse
    {
        $contents = $this->request($url, $options);
        if ($contents->code === SpiderResponse::CODE_VERCODE) {
            return $contents;
        } elseif ($contents->code === SpiderResponse::CODE_EMPTY) {
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
            $options  = [
                "headers" => $headers
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
        if ($this->autoRun && !$this->isRunning) {
            $this->isRunning = true;
            $this->lc = loop(function () use ($queue) {
                for ($i = 0; $i < $this->num; $i++) {
                    $task = $queue->pop();
                    rgo(function () use ($task) {
                        $task($this);
                    });
                }
            }, $this->limit * 1000);
        }
    }
}
