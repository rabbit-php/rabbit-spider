<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy;

use Rabbit\Spider\AbstractProxyPlugin;
use Throwable;
use Rabbit\Base\App;
use Rabbit\Base\Exception\InvalidArgumentException;
use Rabbit\Data\Pipeline\Message;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\HttpClient\Client;
use Swlib\SaberGM;
use Symfony\Component\DomCrawler\Crawler;
use Rabbit\Spider\Proxy\Domains\AbstractDomain;
use Rabbit\Spider\Source\IP;
use Swlib\Saber\Request;

/**
 * Class GetProxy
 * @package Spider\Proxy
 */
class GetProxy extends AbstractProxyPlugin
{
    protected ?array $domains = [];

    protected string $classPrefix = 'Rabbit\Spider\Proxy\Domains';

    protected array $proxy = [];
    /**
     * @throws Throwable
     */
    public function init(): void
    {
        parent::init();
        [$this->domains, $proxy] = ArrayHelper::getValueByArray($this->config, ['domains', 'proxy']);
        if ($this->domains === null) {
            throw new InvalidArgumentException("domains is empty!");
        }
        if ($proxy) {
            foreach (explode(',', $proxy) as $ip) {
                $parsed_url = parse_url($ip);
                $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
                $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
                $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
                $ip = new IP($res);
                $ip->validate();
                $this->proxy[] = $ip;
            }
        }
    }

    /**
     * @param Message $msg
     * @return void
     * @throws Throwable
     * @author Albert <63851587@qq.com>
     */
    public function run(Message $msg): void
    {
        $useProxy = count($this->proxy);
        foreach ($this->domains as $domain => $timeout) {
            rgo(function () use ($domain, $timeout, $msg, $useProxy) {
                $model = $this->classPrefix . '\\' . $domain;
                /** @var AbstractDomain $domain */
                $domain = create($model);
                $model = substr($model, strrpos($model, '\\') + 1);
                while (true) {
                    $realSleep = mt_rand(1, (int)ceil((int)$timeout / 3));
                    foreach ($domain->getTypes() as $type) {
                        $index = 1;
                        while (true) {
                            foreach ($domain->getUrls($index, $type) as $url) {
                                try {
                                    App::debug("$model get $index");
                                    $options = [
                                        'use_pool' => true,
                                        'timeout' => $timeout * 5,
                                        'iconv' => $domain->getEncoding() ?? false,
                                        'headers' => [
                                            'Upgrade-Insecure-Requests' => "1",
                                            'Accept-Encoding' => null,
                                            'DNT' => "1"
                                        ]
                                    ];
                                    if ($useProxy > 0) {
                                        $ip = $this->proxy[array_rand($this->proxy)];
                                        if ($ip->proxy) {
                                            $options['proxy'] = "tcp://{$ip->proxy}";
                                            $options['pool_key'] = static function (Request $request) {
                                                return Client::getKey($request->getConnectionTarget() + $request->getProxy());
                                            };
                                        }
                                    }
                                    $response = SaberGM::get($url, $options);
                                    $body = $response->getBody()->getContents();
                                    if (empty($body)) {
                                        break 2;
                                    }
                                    $crawler = new Crawler($body);
                                    $tmp = clone $msg;
                                    $tmp->data = [];
                                    $total = $domain->getPages($crawler);
                                    if (null !== $selector = $domain->getSelector()) {
                                        $table = $crawler->filterXPath($selector);
                                        $table->each(function (Crawler $node) use ($domain, $tmp) {
                                            if (!empty($data = $domain->buildData($node))) {
                                                $tmp->data[] = array_combine($this->manager->attributes, [...$data, 91, 1, 0]);
                                            }
                                        });
                                    } else {
                                        foreach ($domain->buildData($crawler) as $data) {
                                            if (!empty($data)) {
                                                $tmp->data[] = array_combine($this->manager->attributes, [...$data, 91, 1, 0]);
                                            }
                                        }
                                    }
                                    $tmp->data = array_filter($tmp->data);
                                    if (empty($tmp->data)) {
                                        App::warning("$model page {$index} get empty");
                                        if ($index >= $total) {
                                            break 2;
                                        }
                                    }
                                    if (++$index >= $total) {
                                        break 2;
                                    }
                                    usleep(200 * 1000);
                                    $this->sink($tmp);
                                } catch (Throwable $exception) {
                                    App::warning("$model get error.msg=" . $exception->getMessage());
                                    break 3;
                                }
                            }
                        }
                    };
                    sleep($realSleep);
                }
            });
        }
    }
}
