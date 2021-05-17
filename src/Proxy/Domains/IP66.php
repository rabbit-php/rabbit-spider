<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IP89
 * @package Spider\Domains
 */
class IP66 extends AbstractDomain
{
    public static string $tableSelector = '//*[@id="main"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.66ip.cn/index.html",
            "http://www.66ip.cn/2.html",
            "http://www.66ip.cn/3.html",
            "http://www.66ip.cn/4.html",
            "http://www.66ip.cn/5.html",
            "http://www.66ip.cn/6.html",
            "http://www.66ip.cn/7.html",
            "http://www.66ip.cn/8.html",
            "http://www.66ip.cn/9.html",
            "http://www.66ip.cn/10.html",
            "http://www.66ip.cn/11.html",
            "http://www.66ip.cn/12.html",
            "http://www.66ip.cn/13.html",
            "http://www.66ip.cn/14.html",
            "http://www.66ip.cn/15.html",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $anonymity = 2;
        $protocol = 'http';
        $location = $node->filterXPath('.//td[3]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}
