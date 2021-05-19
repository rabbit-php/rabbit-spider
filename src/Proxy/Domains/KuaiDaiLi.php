<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class KuaiDaiLi
 * @package Spider\Domains
 */
class KuaiDaiLi extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@id="list"]/table/tbody/tr';
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
        $ha = $type === 1 ? 'inha' : 'intr';
        if ($i === 1) {
            yield "https://www.kuaidaili.com/free/{$ha}/";
        } else {
            yield "https://www.kuaidaili.com/free/{$ha}/{$i}";
        }
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $anonymity = $node->filterXPath('.//td[3]')->text() === "高匿名" ? 2 : 1;
        $protocol = strtolower($node->filterXPath('.//td[4]')->text());
        $location = $node->filterXPath('.//td[5]')->text();
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
