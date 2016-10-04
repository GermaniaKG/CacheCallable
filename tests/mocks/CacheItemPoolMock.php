<?php
namespace mocks;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class CacheItemPoolMock implements CacheItemPoolInterface
{
    public $items = array();
    public $deferred_items = array();

    public function __construct()
    {
    }

    public function save(CacheItemInterface $item)
    {
        $this->items[ $item->getKey() ] = $item;
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred_items[$item->getKey()] = $item;
    }

    public function commit()
    {
        while ($item = array_shift($this->deferred_items)) {
            $this->save( $item );
        }
    }

    public function getItem($key)
    {
        if (!$this->hasItem($key)) {
            return new CacheItemMock($key, null, false);
        }
        return $this->items[$key];
    }

    public function getItems( array $keys = array()) {
        $items = array();
        while ($key = array_shift($keys)) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function hasItem( $key ) {
        return (array_key_exists($key, $this->items)
        and !empty($this->items[$key]));
    }

    public function clear()
    {
        $this->items = array();
    }
    public function deleteItem( $key ) {
        $this->items[$key] = null;
    }
    public function deleteItems( array $keys )
    {
        while ($key = array_shift($keys)) {
            $this->deleteItem($key);
        }
    }


}
