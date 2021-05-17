<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IP3366
 * @package Spider\Domains
 */
class IP3366 extends AbstractDomain
{
    public static string $tableSelector = '//*[@id="list"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.ip3366.net/free/?stype=1&page=1",
            "http://www.ip3366.net/free/?stype=1&page=2",
            "http://www.ip3366.net/free/?stype=1&page=3",
            "http://www.ip3366.net/free/?stype=1&page=4",
            "http://www.ip3366.net/free/?stype=2&page=1",
            "http://www.ip3366.net/free/?stype=2&page=2",
            "http://www.ip3366.net/free/?stype=2&page=3",
            "http://www.ip3366.net/free/?stype=2&page=4",
            "http://www.ip3366.net/free/?stype=3&page=1",
            "http://www.ip3366.net/free/?stype=3&page=2",
            "http://www.ip3366.net/free/?stype=3&page=3",
            "http://www.ip3366.net/free/?stype=3&page=4",
            "http://www.ip3366.net/free/?stype=4&page=1",
            "http://www.ip3366.net/free/?stype=4&page=2",
            "http://www.ip3366.net/free/?stype=4&page=3",
            "http://www.ip3366.net/free/?stype=4&page=4",
        ];
    }

    /**
     * @return string
     */
    public static function getEncoding(): string
    {
        return 'gb2312';
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "高匿") !== false ? 2 : 1;
        $protocol = strtolower($node->filterXPath('.//td[4]')->text());
        $location = $node->filterXPath('.//td[5]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}