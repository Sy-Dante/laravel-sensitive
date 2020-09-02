<?php

namespace Sydante\LaravelSensitive;

interface SensitiveCacheInterface
{
    public function setKey(string $key): void;

    public function get(): ?array;

    public function set(array $trieTreeMap): bool;
}
