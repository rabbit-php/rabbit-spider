<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AbstractDomain
 * @package Spider\Domains
 */
abstract class AbstractDomain
{
    protected ?string $tableSelector = null;

    public function getSelector(): ?string
    {
        return $this->tableSelector;
    }
    /**
     * @return string
     */
    public function getEncoding(): ?array
    {
        return ['auto', 'utf-8', false];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    abstract public function buildData(Crawler $node): array;

    /**
     * @return array
     */
    abstract public function getUrls(int $i, int $type): Generator;

    abstract public function getTypes(): array;

    abstract public function getPages(Crawler $crawler): int;
}
