<?php

declare(strict_types=1);

namespace Tests\Webgriffe\PayumLockRequestExtension;

use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\PersistingStoreInterface;

final class InMemoryStore implements PersistingStoreInterface
{
    public function __construct(
        public array $locks = [],
    ) {
    }

    public function save(Key $key): void
    {
        $this->locks[(string) $key] = $key;
    }

    public function delete(Key $key): void
    {
        unset($this->locks[(string) $key]);
    }

    public function exists(Key $key): bool
    {
        return array_key_exists((string) $key, $this->locks);
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        // do nothing, memory locks forever.
    }
}
