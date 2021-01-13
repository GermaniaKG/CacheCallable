<?php
namespace Germania\Cache;

class Md5CacheKeyCreator
{

    /**
     * @param  string|mixed $raw_key Raw Cache key identifier
     * @return string MD5 encoded cache key
     */
    public function __invoke( $raw_key ) : string
    {
        return md5( is_string($raw_key) ? $raw_key : serialize($raw_key), false );
    }
}
