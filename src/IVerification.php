<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Symfony\Component\DomCrawler\Crawler;

interface IVerification
{
    public function verificationCode(SpiderResponse $response): ?Crawler;
}
