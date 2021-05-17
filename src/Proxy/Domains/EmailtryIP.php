<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class EmailtryIP
 * @package Spider\Domains
 */
class EmailtryIP extends AbstractDomain
{
    public static string $tableSelector = '//*[@id="proxy-table1"]/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://emailtry.com/index/1",
            "http://emailtry.com/index/2",
            "http://emailtry.com/index/3",
            "http://emailtry.com/index/4",
            "http://emailtry.com/index/5",
            "http://emailtry.com/index/6",
            "http://emailtry.com/index/7",
            "http://emailtry.com/index/8",
            "http://emailtry.com/index/9",
            "http://emailtry.com/index/10",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        [$ip, $port] = explode(':', $node->filterXPath('.//td[1]')->text());
        $anonymity = strpos($node->filterXPath('.//td[2]')->text(), "High") !== false ? 2 : 1;
        $protocol = 'http';
        $location = $node->filterXPath('.//td[3]')->text() . ' ' . $node->filterXPath('.//td[4]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}