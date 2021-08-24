<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Contract\ArrayAble;
use Rabbit\Base\Core\LoopControl;
use Rabbit\HttpClient\Client;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;
use Rabbit\Model\Model;
use Rabbit\Spider\Exception\EmptyException;
use Rabbit\Spider\SpiderResponse;
use Swlib\Saber\Request;
use Throwable;

class IP extends Model implements ArrayAble
{
    public ?int $id = null;
    public ?int $ip2long = null;
    public ?string $ip = null;
    public ?int $port = null;
    public ?string $user = null;
    public ?string $pass = null;
    public int $num = 10;
    public bool $release = true;
    public ?int $checktime = null;
    public ?string $proxy = null;
    public int $source = -1;
    public int $timeout = 10;
    public ?int $duration = 1;

    protected ?AbstractSource $ctrl = null;
    protected array $lc = [];
    protected array $hostNum = [];

    const IP_VCODE = 0;
    const IP_FAILED = -1;

    protected Client $client;

    public function __construct(array $columns = [], AbstractSource $ctrl = null)
    {
        parent::__construct($columns);
        $this->ctrl = $ctrl;
        $this->validate();

        $this->client = new Client([
            'use_pool' => true,
            "target" => true,
            "iconv" => false,
            "redirect" => 0,
            'timeout'  => $this->timeout,
            'headers'  => [
                'DNT' => "1",
                'Accept' => '*/*',
            ]
        ]);
    }

    public function rules(): array
    {
        return [
            [['proxy'], function () {
                $host = !empty($this->ip) && $this->ip !== '127.0.0.1' ? $this->ip : '';
                $port = !empty($this->port) ? ":{$this->port}" : '';
                $user = !empty($this->user) ? $this->user : '';
                $pass = !empty($this->pass) ? ':' . $this->pass : '';
                $pass = ($user && $pass) ? "$pass@" : '';
                return "$user$pass$host$port";
            }]
        ];
    }

    public function loop(): void
    {
        foreach ($this->ctrl->getManager()->getQueue() as $host => $queue) {
            if (!($this->lc[$host] ?? false)) {
                $this->lc[$host] = new LoopControl(0);
                $this->hostNum[$host] = $this->num;
                loop(function () use ($queue, $host) {
                    if ($this->hostNum[$host] === 0) {
                        $this->lc[$host]->stop();
                        if ($this->lc[$host]->loop === false) {
                            return;
                        }
                    }
                    $queue->enqueue($this);
                    $this->hostNum[$host]--;
                }, 0, $this->lc[$host]);
            }
        }
    }

    public function shutdown(string $host = null): void
    {
        if ($host === null) {
            foreach ($this->lc as $lc) {
                $lc->shutdown();
            }
        } elseif ($this->lc[$host] ?? false) {
            $this->lc[$host]->shutdown();
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function proxy(string $url, array $options = []): SpiderResponse
    {
        $contents = $this->request($url, $options);
        if ($contents->code === SpiderResponse::CODE_EMPTY) {
            throw new EmptyException("No body with response");
        } elseif (!$contents->isOK) {
            throw new BadRequestHttpException("got error! code={$contents->code}");
        }
        return $contents;
    }

    private function request(string $url, array $headers = []): SpiderResponse
    {
        $response = new SpiderResponse();
        $key = null;
        try {
            $options = [
                'pool_key' => function (Request $request) use (&$key) {
                    $key = Client::getKey($request->getConnectionTarget() + $request->getProxy());
                    return $key;
                },
                'headers' => $headers
            ];
            if (!empty($this->proxy)) {
                $options['proxy'] = [
                    'http' => "tcp://{$this->proxy}",
                    'https' => "tcp://{$this->proxy}",
                ];
            }
            $response->setResponse($this->client->get($url, $options));
        } catch (Throwable $e) {
            $response->code = $e->getCode();
        } finally {
            try {
                $this->ctrl->getManager()->verification($url, $response);
                if ($response->code === SpiderResponse::CODE_VERCODE) {
                    $this->duration = self::IP_VCODE;
                } elseif ($response->isOK && $this->duration > 0 && (null !== $res = $response->getResponse())) {
                    $this->duration = $res->getDuration();
                } else {
                    $this->duration = self::IP_FAILED;
                }
                $host = parse_url($url, PHP_URL_HOST);
                if ($this->release && $this->ctrl->update($host, $this)) {
                    $key && Client::release($key);
                }
            } finally {
                if ($this->release) {
                    $this->hostNum[$host]++;
                    $this->lc[$host]->start();
                }
            }
            return $response;
        }
    }
}
