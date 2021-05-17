<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ProxyList
 * @package Spider\Proxy\Domains
 */
class ProxyList extends AbstractDomain
{
    public static string $tableSelector = '';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "https://www.proxy-list.download/api/v1/get?type=http",
            "https://www.proxy-list.download/api/v1/get?type=https",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
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
}