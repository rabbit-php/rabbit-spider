<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy;

use Rabbit\Spider\AbstractProxyPlugin;
use Throwable;
use Rabbit\Base\App;
use Rabbit\Base\Exception\InvalidArgumentException;
use Rabbit\Data\Pipeline\Message;
use Rabbit\Base\Helper\ArrayHelper;
use Swlib\SaberGM;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class GetProxy
 * @package Spider\Proxy
 */
class GetProxy extends AbstractProxyPlugin
{
    protected ?array $domains = [];

    protected string $classPrefix = 'Rabbit\Spider\Proxy\Domains';

    protected string $poolDomain;
    /**
     * @throws Throwable
     */
    public function init(): void
    {
        parent::init();
        [$this->domains, $this->poolDomain] = ArrayHelper::getValueByArray($this->config, ['domains', 'poolDomain']);
        if ($this->domains === null || $this->poolDomain === null) {
            throw new InvalidArgumentException("domains or poolDomain is empty!");
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
        foreach ($this->domains as $domain => $timeout) {
            rgo(function () use ($domain, $timeout, $msg) {
                $domain = $this->classPrefix . '\\' . $domain;
                while (true) {
                    $realSleep = mt_rand(1, (int)ceil((int)$timeout / 3));
                    $urls = $domain::getUrls();
                    wgeach($urls, function (int $index, string $url) use ($domain, $realSleep, $urls, $timeout, $msg) {
                        try {
                            $options = [
                                'use_pool' => true,
                                'timeout' => $timeout * 5,
                                'iconv' => [$domain::getEncoding(), 'utf-8', true],
                                'headers' => [
                                    'Upgrade-Insecure-Requests' => "1",
                                    'Accept-Encoding' => null,
                                    'DNT' => "1"
                                ]
                            ];
                            // $proxy = $this->proxy->getOneIP($this->poolDomain);
                            // if ($proxy) {
                            //     $options['use_pool'] = false;
                            //     $options['proxy'] = [
                            //         'http' => 'tcp://' . $proxy['ip'] . ':' . $proxy['port'],
                            //         'https' => 'tcp://' . $proxy['ip'] . ':' . $proxy['port']
                            //     ];
                            // }
                            $response = SaberGM::get($url, $options);
                            $crawler = new Crawler();
                            $body = $response->getBody()->getContents();
                            if (empty($body)) {
                                sleep($$realSleep);
                                return;
                            }
                            $crawler->addHtmlContent($body);
                            $tmp = clone $msg;
                            $tmp->data = [];
                            if ($domain::$tableSelector) {
                                $table = $crawler->filterXPath($domain::$tableSelector);
                                $table->each(function (Crawler $node) use ($domain, $tmp) {
                                    $tmp->data[] = array_combine($this->manager->attributes, [...$domain::buildData($node), 91, 1, 0]);
                                });
                            } else {
                                foreach ($domain::buildData($crawler) as $data) {
                                    $tmp->data[] = array_combine($this->manager->attributes, [...$data, 91, 1, 0]);
                                }
                            }
                            if (empty($tmp->data)) {
                                App::warning("$domain get empty");
                                return;
                            }
                            $this->sink($tmp);
                        } catch (Throwable $exception) {
                            App::warning("$domain get error.code=" . $exception->getCode());
                            return;
                        }
                        if (count($urls) - 1 === $index) {
                            App::info("$domain finished");
                        }
                    }, 300);
                    sleep($realSleep);
                }
            });
        }
    }
}
