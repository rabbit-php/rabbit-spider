<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class KXDaiLi
 * @package Spider\Domains
 */
class KXDaiLi extends AbstractDomain
{
    public static string $tableSelector = '//*[@class="hot-product-content"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.kxdaili.com/dailiip.html",
            "http://www.kxdaili.com/dailiip/1/2.html",
            "http://www.kxdaili.com/dailiip/1/3.html",
            "http://www.kxdaili.com/dailiip/1/4.html",
            "http://www.kxdaili.com/dailiip/1/5.html",
            "http://www.kxdaili.com/dailiip/1/6.html",
            "http://www.kxdaili.com/dailiip/1/7.html",
            "http://www.kxdaili.com/dailiip/2/1.html",
            "http://www.kxdaili.com/dailiip/2/2.html",
            "http://www.kxdaili.com/dailiip/2/3.html",
            "http://www.kxdaili.com/dailiip/2/4.html",
            "http://www.kxdaili.com/dailiip/2/5.html",
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
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "高匿") !== false ? 2 : 1;
        $protocol = strpos(strtolower($node->filterXPath('.//td[4]')->text()), 'https') !== false ? 'https' : 'http';
        $location = $node->filterXPath('.//td[6]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}