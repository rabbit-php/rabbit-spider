<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Contract\ArrayAble;
use Rabbit\HttpClient\Client;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;
use Rabbit\Model\Model;
use Rabbit\Spider\Agents\UserAgent;
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
    public int $wait = 100 * 1000;
    public bool $isLocal = false;

    protected ?AbstractSource $ctrl = null;
    protected array $hosts = [];

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
        ], session: true);
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

    public function addHost(string $host): bool
    {
        if (in_array($host, $this->hosts)) {
            return false;
        }
        $this->hosts[] = $host;
        return true;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function proxy(string $url, array $options = [], int $retry = 1): SpiderResponse
    {
        while ($retry--) {
            $contents = $this->request($url, $options);
            if ($contents->code === SpiderResponse::CODE_EMPTY) {
                $contents = null;
                if ($retry === 0) {
                    break;
                }
                usleep($this->wait);
                continue;
            } elseif (!$contents->isOK) {
                throw new BadRequestHttpException("got error! code={$contents->code}");
            }
            return $contents;
        }
        throw new EmptyException("No body with response");
    }

    private function request(string $url, array $options = []): SpiderResponse
    {
        $response = new SpiderResponse();
        $key = null;
        $ciphers = $this->ctrl->getManager()->getCiphers();
        try {
            shuffle($ciphers);
            $options = array_merge($options, [
                'pool_key' => function (Request $request) use (&$key) {
                    $key = Client::getKey($request->getConnectionTarget() + $request->getProxy());
                    return $key;
                },
                'ssl_ciphers' => implode(':', $ciphers),
                'useragent' => UserAgent::random([
                    'os_type' => 'Windows',
                    'device_type' => 'Desktop'
                ])
            ]);
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
            $this->ctrl->getManager()->verification($url, $response);
            if ($response->code === SpiderResponse::CODE_VERCODE) {
                $this->duration = self::IP_VCODE;
            } elseif ($response->isOK && $response->code > 0) {
                $this->duration = $response->getResponse()->getDuration();
            } else {
                $this->duration = self::IP_FAILED;
            }
            $host = parse_url($url, PHP_URL_HOST);
            if ($this->release && $this->ctrl->release($host, $this)) {
                $key && $this->source < 0 && Client::release($key);
            }
            return $response;
        }
    }
}
