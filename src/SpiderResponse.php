<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\HttpClient\Response;
use Rabbit\Spider\Exception\FailedException;
use Rabbit\Web\SwooleStream;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class SpiderResponse
{
    private ?Response $response = null;

    private ?Crawler $crawler = null;

    public int $code = 0;

    public ?string $msg = null;

    public bool $isOK = false;

    const CODE_VERCODE = -4;
    const CODE_EMPTY = -5;
    const CODE_FAILED = -6;

    public function __construct(Response $response = null)
    {
        $this->setResponse($response);
    }

    public function __destruct()
    {
        libxml_clear_errors();
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
            if ($this->code <= 0) {
                return;
            }
            if ($response->getBody()->getSize() === 0) {
                $this->code = self::CODE_EMPTY;
            }
            if (2 === (int)($this->code / 100) % 10) {
                $this->isOK = true;
            }
        }
    }

    public function getCrawler(): ?Crawler
    {
        if ($this->code <= 0) {
            return null;
        }
        try {
            if ($this->crawler !== null) {
                return $this->crawler;
            }
            $this->crawler = new Crawler();
            $this->crawler->addDocument($this->response->domObject());
            $this->response->withBody(new SwooleStream());
            return $this->crawler;
        } catch (Throwable $e) {
            $this->msg = $e->getMessage();
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
        } catch (FailedException $e) {
            $this->code = self::CODE_FAILED;
        } catch (Throwable $e) {
            $this->code = self::CODE_EMPTY;
        }
    }
}
