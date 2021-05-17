<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\HttpClient\Response;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class SpiderResponse
{
    private ?Response $response = null;

    private ?Crawler $crawler = null;

    public int $code = 0;

    public bool $isOK = false;

    const CODE_VERCODE = -1;
    const CODE_EMPTY = -2;
    const CODE_FAILED = 0;

    public function __construct(Response $response = null)
    {
        $this->setResponse($response);
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
        if ($response !== null) {
            $this->code = $response->getStatusCode();
            if (2 === ($this->code / 100) % 10) {
                $this->isOK = true;
            }
            if ($response->getBody()->getSize() === 0) {
                $this->code = self::CODE_EMPTY;
            }
        }
    }

    public function getCrawler(): ?Crawler
    {
        if ($this->code === self::CODE_VERCODE) {
            return null;
        }
        try {
            $this->crawler ??= new Crawler($this->response->getBody()->getContents());
            return $this->crawler;
        } catch (Throwable $e) {
            $this->code = self::CODE_EMPTY;
            return null;
        }
    }

    public function verification(IVerification $check): void
    {
        try {
            if (null === $this->crawler = $check->verificationCode($this)) {
                $this->code = self::CODE_VERCODE;
            }
        } catch (Throwable $e) {
            $this->code = self::CODE_EMPTY;
        }
    }
}
