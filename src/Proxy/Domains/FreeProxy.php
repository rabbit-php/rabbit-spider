<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

class FreeProxy extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@id="proxy_list"]/tbody/tr';
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
        yield "http://free-proxy.cz/zh/proxylist/main/{$i}";
    }

    public function buildData(Crawler $node): ?array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        if (!str_contains($ip, 'Base64.decode')) {
            return null;
        }
        $ip = base64_decode(substr($ip, strpos($ip, '"'), strrpos($ip, '"')));
        $port = $node->filterXPath('.//td[2]')->text();
        $protocol = strtolower($node->filterXPath('.//td[3]')->text('http'));
        $location = $node->filterXPath('.//td[4]/div/a')->text('') . ' ' . $node->filterXPath('.//td[5]')->text('') . ' ' . $node->filterXPath('.//td[6]')->text('');
        $anonymity = str_contains($node->filterXPath('.//td[7]')->text('高匿'), '高匿') ? 2 : 1;
        return [ip2long($ip), $ip, (int)$port, $anonymity, $protocol, $location];
    }

    public function getPages(Crawler $crawler): int
    {
        $pages = [];
        $page = $crawler->filterXPath('//*/body/div[2]/div[2]/div[4]/a');
        $page->each(function (Crawler $node) use (&$pages) {
            $pages[] = (int)$node->filterXPath('.//a')->text('0');
        });
        return max($pages);
    }
}
