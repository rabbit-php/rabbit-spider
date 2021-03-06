<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy;

use Throwable;
use Rabbit\HttpClient\Client;
use Rabbit\Data\Pipeline\Message;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\Base\Exception\InvalidConfigException;
use Rabbit\Spider\AbstractProxyPlugin;
use Rabbit\Spider\Agents\UserAgent;
use Rabbit\Spider\Source\IP;
use Rabbit\Spider\SpiderResponse;

/**
 * Class CheckProxy
 * @package Spider\Proxy
 */
class CheckProxy extends AbstractProxyPlugin
{
    protected ?array $checkList;
    protected ?int $sleep;
    protected Client $client;

    public function init(): void
    {
        parent::init();
        [
            $this->checkList,
            $this->sleep
        ] = ArrayHelper::getValueByArray($this->config, ['checkList', 'sleep'], [[], 3]);
        if (empty($this->checkList)) {
            throw new InvalidConfigException("checkList empty");
        }
        $this->client = new Client([
            'use_pool' => false,
            'target' => false,
            "redirect" => 5,
        ]);
    }

    /**
     * @param Message $msg
     * @throws Throwable
     */
    public function run(Message $msg): void
    {
        if (empty($msg->data) || empty($this->checkList)) {
            return;
        }

        wgeach($this->checkList, function (string $url, int $timeout) use ($msg): void {
            if (isset($msg->opt['check']) && $msg->opt['check'] !== parse_url($url)) {
                return;
            }
            $data = $msg->data;
            $tmp = clone $msg;
            $tmp->data = $data;
            $data = null;
            wgeach($tmp->data, function (int $i, array &$item) use ($url, $timeout): void {
                $proxy = "{$item['ip']}:{$item['port']}";
                $response = new SpiderResponse();
                try {
                    $response->setResponse($this->client->get($url, [
                        "proxy"   => [
                            "http"  => "tcp://$proxy",
                            "https" => "tcp://$proxy",
                        ],
                        'iconv' => false,
                        'redirect' => 0,
                        'timeout' => $timeout,
                        'useragent' => UserAgent::random([
                            'agent_type' => 'Browser',
                            'os_type' => 'Windows',
                            'device_type' => 'Desktop'
                        ])
                    ]));
                } catch (Throwable $exception) {
                    $response->code = $exception->getCode();
                } finally {
                    if ($response->code === SpiderResponse::CODE_VERCODE) {
                        $item['duration'] = IP::IP_VCODE;
                    } elseif ($response->isOK && $response->code > 0) {
                        $item['duration'] = $response->getResponse()->getDuration();
                    } else {
                        $item['duration'] = IP::IP_FAILED;
                    }
                }
            });
            $tmp->opt['table'] = $url;
            $this->sink($tmp);
        });
    }
}
