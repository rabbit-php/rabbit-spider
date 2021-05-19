<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ProxyList
 * @package Spider\Proxy\Domains
 */
class ProxyList extends AbstractDomain
{
    public function getTypes(): array
    {
        return [1, 2];
    }

    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type): Generator
    {
        $ha = 'http';
        if ($type !== 1) {
            $ha = 'https';
        }
        yield "https://www.proxy-list.download/api/v1/get?type={$ha}";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        $html = $node->last()->text();
        $lines = explode(" ", $html);
        $rows = [];
        foreach ($lines as $line) {
            if (strpos($line, ":") === false) {
                continue;
            }
            $line = str_replace("\r", "", $line);
            $line = str_replace("\n", "", $line);
            list($ip, $port) = explode(":", $line);
            $anonymity = 1;
            $protocol = "http";
            $rows[] = [ip2long($ip), $ip, $port, $anonymity, $protocol, ''];
        }
        return $rows;
    }

    public function getPages(Crawler $crawler): int
    {
        return 1;
    }
}
