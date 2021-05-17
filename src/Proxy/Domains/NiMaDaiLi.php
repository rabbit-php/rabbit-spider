<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class NiMaDaiLi
 * @package Spider\Domains
 */
class NiMaDaiLi extends AbstractDomain
{
    public static string $tableSelector = '//*[@class="mt-0 mb-2 table-responsive"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.nimadaili.com/putong/",
            "http://www.nimadaili.com/putong/2/",
            "http://www.nimadaili.com/putong/3/",
            "http://www.nimadaili.com/putong/4/",
            "http://www.nimadaili.com/putong/5/",
            "http://www.nimadaili.com/gaoni/1/",
            "http://www.nimadaili.com/gaoni/2/",
            "http://www.nimadaili.com/gaoni/3/",
            "http://www.nimadaili.com/gaoni/4/",
            "http://www.nimadaili.com/gaoni/5/",
            "http://www.nimadaili.com/http/1/",
            "http://www.nimadaili.com/http/2/",
            "http://www.nimadaili.com/http/3/",
            "http://www.nimadaili.com/http/4/",
            "http://www.nimadaili.com/http/5/",
            "http://www.nimadaili.com/https/1/",
            "http://www.nimadaili.com/https/2/",
            "http://www.nimadaili.com/https/3/",
            "http://www.nimadaili.com/https/4/",
            "http://www.nimadaili.com/https/5/",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        [$ip, $port] = explode(':', $node->filterXPath('.//td[1]')->text());
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "普通") !== false ? 1 : 2;
        $protocol = strpos(strtolower($node->filterXPath('.//td[2]')->text()), 'https') !== false ? 'https' : 'http';
        $location = $node->filterXPath('.//td[4]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}