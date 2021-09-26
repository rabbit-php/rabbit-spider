<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use DOMDocument;
use Rabbit\Base\Core\SplChannel;
use Rabbit\Base\Core\StaticInstanceTrait;

class ParserPool
{
    use StaticInstanceTrait;
    
    protected int $size = 10;

    protected SplChannel $channel;

    protected int $current = 0;

    public function __construct(int $size = 10)
    {
        $this->size = $size;
        $this->channel = new SplChannel();
    }

    public function get(string &$html): DOMDocument
    {
        if (!$this->channel->isEmpty() || $this->current >= $this->size) {
            $dom = $this->channel->dequeue();
        } else {
            $this->current++;
            $dom = new DOMDocument();
            $this->channel->enqueue($dom);
        }
        $this->loadHTML($dom, $html);
        return $dom;
    }

    public function release(DOMDocument $dom): void
    {
        $html = '<html><body></body></html>';
        $this->loadHTML($dom, $html);
        $this->channel->enqueue($dom);
    }

    private function loadHTML(DOMDocument $dom, string &$html): void
    {
        $bak = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($bak);
    }
}
