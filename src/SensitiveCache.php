<?php

namespace Sydante\LaravelSensitive;

use Illuminate\Support\Facades\Cache;

class SensitiveCache implements SensitiveCacheInterface
{
    private $key;

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function get(): ?array
    {
        return Cache::get($this->key);
    }

    public function set(array $trieTreeMap): bool
    {
        return Cache::forever($this->key, $trieTreeMap);
    }
}
