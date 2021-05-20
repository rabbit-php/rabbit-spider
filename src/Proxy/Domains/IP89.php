<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IP89
 * @package Spider\Domains
 */
class IP89 extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@class="layui-form"]/table/tbody/tr';
    }

    public function getTypes(): array
    {
        return [1];
    }
    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type): Generator
    {
        yield "http://www.89ip.cn/index_{$i}.html";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $location = $node->filterXPath('.//td[3]')->text();
        return [ip2long($ip), $ip, (int)$port, 2, 'http', $location];
    }

    public function getPages(Crawler $crawler): int
    {
        return 100;
    }
}
