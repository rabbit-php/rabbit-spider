<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class KXDaiLi
 * @package Spider\Domains
 */
class KXDaiLi extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@class="hot-product-content"]/table/tbody/tr';
    }

    public function getTypes(): array
    {
        return [1, 2];
    }
    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type = 1): Generator
    {
        yield "http://www.kxdaili.com/dailiip/{$type}/{$i}.html";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): ?array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "高匿") !== false ? 2 : 1;
        $protocol = strpos(strtolower($node->filterXPath('.//td[4]')->text()), 'https') !== false ? 'https' : 'http';
        $location = $node->filterXPath('.//td[6]')->text();
        return [ip2long($ip), $ip, (int)$port, $anonymity, $protocol, $location];
    }

    public function getPages(Crawler $crawler): int
    {
        $pages = [];
        $page = $crawler->filterXPath('//*[@id="listnav"]/ul/li');
        $page->each(function (Crawler $node) use (&$pages) {
            $pages[] = (int)$node->filterXPath('.//a')->text('0');
        });
        return max($pages);
    }
}
