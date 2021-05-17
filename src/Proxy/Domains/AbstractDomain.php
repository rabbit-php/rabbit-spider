<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AbstractDomain
 * @package Spider\Domains
 */
abstract class AbstractDomain
{

    /**
     * @return string
     */
    public static function getEncoding(): string
    {
        return 'utf-8';
    }

    /**
     * @param Crawler $node
     * @return array
     */
    abstract public static function buildData(Crawler $node): array;

    /**
     * @return array
     */
    abstract public static function getUrls(): array;
}
