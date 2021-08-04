<?php

declare(strict_types=1);

namespace Rabbit\Spider\Stores;

interface IProxyStore
{
    public function save(array $data, bool $onlyUpdate = false): void;

    public function delete(array $query): void;

    public function getUse(array $query): array;
}
