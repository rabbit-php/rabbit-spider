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

    protected ?string $classPrefix = null;

    protected array $proxy = [];

    protected int $maxEmpty = 10;

    protected array $useProxy = [];
    /**
     * @throws Throwable
     */
    public function init(): void
    {
        parent::init();
        [
            $this->domains,
            $this->classPrefix,
            $this->maxEmpty, $proxy, $this->useProxy
        ] = ArrayHelper::getValueByArray(
            $this->config,
            ['domains', 'classPrefix', 'maxEmpty', 'proxy', 'useProxy'],
            ['maxEmpty' => $this->maxEmpty, 'useProxy' => $this->useProxy]
        );
        if ($this->domains === null || $this->classPrefix === null) {
            throw new InvalidArgumentException("domains or classPrefix is empty!");
        }
        if ($proxy) {
            foreach (explode(',', $proxy) as $ip) {
                $parsed_url = parse_url($ip);
                $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
                $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
                $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
                $res['ip2long'] = ip2long($res['ip']);
                $ip = new IP($res);
                $ip->validate();
                $this->proxy[$ip->ip2long] = $ip;
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
        if ($msg->data && is_array($msg->data)) {
            foreach ($msg->data as $ip2long => $ip) {
                if ($ip instanceof IP && !array_key_exists($ip2long, $this->proxy)) {
                    $this->proxy[$ip2long] = $ip;
                }
            }
        }
        $useProxy = count($this->proxy);
        foreach ($this->domains as $site => $timeout) {
            rgo(function () use ($site, $timeout, $msg, $useProxy): void {
                $model = $this->classPrefix . '\\' . $site;
                /** @var AbstractDomain $domain */
                $domain = create($model);
                $model = substr($model, strrpos($model, '\\') + 1);
                while (true) {
                    $realSleep = mt_rand(1, (int)ceil((int)$timeout / 3));
                    foreach ($domain->getTypes() as $type) {
                        $index = 1;
                        $count = 0;
                        while (true) {
                            foreach ($domain->getUrls($index, $type) as $url) {
                                $retry = 3;
                                while ($retry--) {
                                    try {
                                        App::debug("$model get $index");
                                        $options = [
                                            'use_pool' => true,
                                            'iconv' => false,
                                            'timeout' => $timeout * 5,
                                            'iconv' => $domain->getEncoding() ?? false,
                                            'headers' => [
                                                'DNT' => "1"
                                            ]
                                        ];
                                        if ($useProxy > 0 && in_array($site, $this->useProxy)) {
                                            $ip = $this->proxy[array_rand($this->proxy)];
                                            if ($ip->proxy) {
                                                $options['proxy'] = "tcp://{$ip->proxy}";
                                                $options['pool_key'] = static function (Request $request): string {
                                                    return Client::getKey($request->getConnectionTarget() + $request->getProxy());
                                                };
                                            }
                                        }
                                        $response = SaberGM::get($url, $options);
                                        if ($response->getBody()->getSize() === 0) {
                                            break 3;
                                        }
                                        $crawler = new Crawler();
                                        $crawler->addDocument($response->getParsedDomObject());
                                        libxml_clear_errors();
                                        $tmp = clone $msg;
                                        $tmp->data = [];
                                        if (null !== $selector = $domain->getSelector()) {
                                            $table = $crawler->filterXPath($selector);
                                            $table->each(function (Crawler $node) use ($domain, $tmp): void {
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
                                        $total = $domain->getPages($crawler);
                                        $tmp->data = array_filter($tmp->data);
                                        if (empty($tmp->data)) {
                                            if (++$count >= $this->maxEmpty) {
                                                App::warning("$model $url get empty");
                                                break 3;
                                            }
                                        }
                                        $count = 0;
                                        $this->sink($tmp);
                                        if (++$index >= $total) {
                                            break 3;
                                        }
                                        continue 2;
                                    } catch (Throwable $exception) {
                                    } finally {
                                        sleep($realSleep);
                                    }
                                }
                                App::warning("$model $url get error.msg=" . $exception->getMessage());
                                break 3;
                            }
                        }
                    };
                    sleep($realSleep);
                }
            });
        }
    }
}
