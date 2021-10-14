<?php

declare(strict_types=1);

namespace Rabbit\Spider\Manager;

use Rabbit\Base\Core\SplChannel;
use Rabbit\Base\Exception\InvalidConfigException;
use Rabbit\Spider\SpiderResponse;
use Rabbit\Spider\Stores\IProxyStore;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\DB\Expression;
use Rabbit\Spider\Source\IP;
use Throwable;

final class ProxyManager
{
    protected IProxyStore $store;

    public array $attributes = ['ip2long', 'ip', 'port', 'anonymity', 'protocol', 'location', 'score', 'duration', 'source'];

    private array $checkCode = [];

    protected array $queue = [];

    protected array $localQueue = [];

    public int $timeout = 10;
    protected int $wait = 0;

    private array $sources = [];
    private bool $running = false;

    protected array $ciphers = [];

    private array $tmp = [
        'DHE-RSA-AES256-SHA',
        'DHE-DSS-AES256-SHA',
        'AES256-SHA:KRB5-DES-CBC3-MD5',
        'KRB5-DES-CBC3-SHA',
        'EDH-RSA-DES-CBC3-SHA',
        'EDH-DSS-DES-CBC3-SHA',
        'DES-CBC3-SHA:DES-CBC3-MD5',
        'DHE-RSA-AES128-SHA',
        'DHE-DSS-AES128-SHA',
        'AES128-SHA:RC2-CBC-MD5',
        'KRB5-RC4-MD5:KRB5-RC4-SHA',
        'RC4-SHA:RC4-MD5:RC4-MD5',
        'KRB5-DES-CBC-MD5',
        'KRB5-DES-CBC-SHA',
        'EDH-RSA-DES-CBC-SHA',
        'EDH-DSS-DES-CBC-SHA:DES-CBC-SHA',
        'DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5',
        'EXP-KRB5-DES-CBC-MD5',
        'EXP-KRB5-RC2-CBC-SHA',
        'EXP-KRB5-DES-CBC-SHA',
        'EXP-EDH-RSA-DES-CBC-SHA',
        'EXP-EDH-DSS-DES-CBC-SHA',
        'EXP-DES-CBC-SHA',
        'EXP-RC2-CBC-MD5',
        'EXP-RC2-CBC-MD5',
        'EXP-KRB5-RC4-MD5',
        'EXP-KRB5-RC4-SHA',
        'EXP-RC4-MD5:EXP-RC4-MD5'
    ];

    public function __construct(IProxyStore $store, array $sources = null)
    {
        $this->store = $store;
        $this->sources = $sources ?? $this->sources;
        $this->ciphers = explode(':', 'ECDHE-ECDSA-AES128-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:RSA+AES128');
    }

    public function verification(string $url, SpiderResponse $response): void
    {
        if (key_exists($domain = parse_url($url, PHP_URL_HOST), $this->checkCode)) {
            $response->verification($this->checkCode[$domain]);
        }
    }

    public function getCiphers(): array
    {
        return [...array_slice($this->tmp, 0, rand(1, count($this->tmp) - 1)), ...$this->ciphers];
    }

    public function getStore(): IProxyStore
    {
        return $this->store;
    }

    public function getQueue(): array
    {
        return $this->queue;
    }

    public function getLocalQueue(): array
    {
        return $this->localQueue;
    }

    public function getIP(string $host, bool $local = false, int $wait = 0): IP
    {
        if (!$this->running) {
            $this->running = true;
            foreach ($this->sources as $name => $source) {
                $source->setManager($this);
                loop(function () use ($source, $name) {
                    sync("proxy.{$name}", fn () => $source->loadIP());
                }, $source->getLoopTime() * 1000);
            }
        }
        if (!($this->queue[$host] ?? false)) {
            $this->queue[$host] = new SplChannel();
            $this->localQueue[$host] = new SplChannel();
            foreach ($this->sources as $name => $source) {
                sync("proxy.{$name}", fn () => $source->loadIP());
                $source->run();
            }
        }
        $wait === 0 && $wait = $this->wait;
        $wait > 0 && usleep($this->wait * 1000);

        if ($local) {
            $ip = $this->localQueue[$host]->dequeue();
            if ($ip->isLocal === false) {
                $ip = clone $ip;
                $ip->isLocal = true;
            }
        } else {
            $ip = $this->queue[$host]->dequeue();
        }
        return $ip;
    }

    public function save(string $domain, array $items): void
    {
        $domain = parse_url($domain, PHP_URL_HOST);
        if (!ArrayHelper::isIndexed($items)) {
            $items = [$items];
        }
        $onlyUpdate = false;
        $updateArr = [];
        foreach ($items as $data) {
            if (!isset($data['id'])) {
                if ($data['duration'] > IP::IP_VCODE) {
                    $data['score'] = 100;
                    $updateArr[] = $data;
                }
            } else {
                $onlyUpdate = true;
                if ($data['duration'] <= IP::IP_VCODE) {
                    $data['score'] = new Expression('score-1');
                } else {
                    $data['score'] = 100;
                }
                $updateArr[] = $data;
            }
        }
        if (!empty($updateArr)) {
            $this->store->save($updateArr, $onlyUpdate);
        }
    }

    public function getIPByName(string $name = 'local'): IP
    {
        $idle = $this->getProxyIdle($name);
        $ip = clone $idle[array_rand($idle)];
        $ip->release = false;
        return $ip;
    }

    public function proxy(string $url, string $name = 'local', array $options = [], int $retry = 5): SpiderResponse
    {
        $idle = $this->getProxyIdle($name);
        while ($retry--) {
            try {
                $ip = clone $idle[array_rand($idle)];
                $ip->release = false;
                return $ip->proxy($url, $options);
            } catch (Throwable $e) {
                sleep(1);
            }
        }
        throw $e;
    }

    public function getProxyIdle(string $name = 'local'): array
    {
        if (!($this->sources[$name] ?? false)) {
            throw new InvalidConfigException("$name source not exist!");
        }
        $ctrl = $this->sources[$name];
        $ctrl->setManager($this);
        sync("proxy.{$name}", fn () => $ctrl->loadIP(true));
        return $ctrl->getIdle();
    }
}
