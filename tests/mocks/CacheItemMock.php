<?php
namespace mocks;

use Psr\Cache\CacheItemInterface;

class CacheItemMock implements CacheItemInterface
{
    public $key;
    public $value;
    public $is_hit;
    public $expires_at;
    public $expires_after;

    public function __construct( $key, $value, $is_hit )
    {
        $this->key    = $key;
        $this->value  = $value;
        $this->is_hit = $is_hit;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function get()
    {
        return $this->value;
    }

    public function isHit()
    {
        return $this->is_hit;
    }

    public function set($value)
    {
        $this->value = $value;
        return $this;
    }

    public function expiresAt($expiration)
    {
        $this->expires_at = $expiration;
    }

    public function expiresAfter($time)
    {
        $this->expires_after = $time;
    }
}
