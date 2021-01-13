<?php
namespace Germania\Cache;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use Psr\Cache\CacheItemPoolInterface;

use Stash\Interfaces\ItemInterface as StashItemInterface;
use Stash\Invalidation as StashInvalidation;
use Symfony\Component\Cache\Adapter\AdapterInterface as SymfonyCacheAdapter;

class CacheCallable
{
    use LoggerAwareTrait;

    /**
     * @var LifeTimeInterface
     */
    public $default_lifetime = null;

    /**
     * @var CacheItemPoolInterface
     */
    public $cacheitempool = null;

    /**
     * @var callable
     */
    public $content_creator = null;

    /**
     * @var callable
     */
    public $cache_key_creator = null;


    /**
     * PSR-3 Loglevel name
     * @var string
     */
    public $loglevel_success = LogLevel::INFO;




    /**
     * @param CacheItemPoolInterface $cacheitempool   PSR-6 Cache Item Pool
     * @param int|LifeTimeInterface  $lifetime        Item's default lifetime in seconds or LifeTime object
     * @param Callable               $content_creator Callable for content creation
     * @param LoggerInterface        $logger          Optional PSR-3 Logger; defaults to NullLogger
     */
    public function __construct(CacheItemPoolInterface $cacheitempool, $lifetime, callable $content_creator, LoggerInterface $logger = null, string $loglevel_success = null)
    {
        $this->setCacheKeyCreator( null );
        $this->setCacheItemPool($cacheitempool);
        $this->default_lifetime = LifeTime::create($lifetime);
        $this->content_creator  = $content_creator;
        $this->setLogger( $logger ?: new NullLogger);
        $this->loglevel_success  = $loglevel_success ? $loglevel_success : $this->loglevel_success;
    }



    public function setCacheItemPool( CacheItemPoolInterface $cacheitempool)
    {
        $this->cacheitempool = $cacheitempool;

        if ($cacheitempool instanceOf SymfonyCacheAdapter) {
            $this->setCacheKeyCreator(new Md5CacheKeyCreator);
        }

        return $this;
    }


    public function setCacheKeyCreator( $creator )
    {
        $creator = $creator ?: function($raw) { return $raw; };
        $this->cache_key_creator = $creator;
        return $this;
    }


    public function setSuccessLoglevel( string $loglevel)
    {
        $this->loglevel_success = $loglevel;
        return $this;
    }



    /**
     * @param string                 $keyword         Cache item identifier
     * @param Callable               $content_creator Optional: Callable override for content creation
     * @param int|LifeTimeInterface  $lifetime        Optional: Custom lifetime for item in seconds or LifeTime object
     * @return mixed
     */

    public function __invoke($keyword, callable $content_creator = null, $lifetime = null)
    {
        $lifetime        = LifeTime::create($lifetime ?: $this->default_lifetime);
        $logger          = $this->logger;
        $cacheitempool   = $this->cacheitempool;

        if (is_callable($content_creator)) {
            $content_creator_type = "custom";
        } else {
            $content_creator_type = "default";
            $content_creator = $this->content_creator;
        }

        $logger->info("Request item", [
            'keyword' => $keyword,
            'contentCreator' => $content_creator_type
        ]);


        // Always rebuild when no cache lifetime given
        $lifetime_value = $lifetime->getValue();
        if ($lifetime_value > 0) :
            $logger->debug("Caching enabled", [ 'lifetime' => $lifetime_value ]);
        else:
            $logger->log($this->loglevel_success, "Cache lifetime is empty, must create content", [
                'keyword' => $keyword
            ]);
            $result = $content_creator($keyword);
            return $result;
        endif;


        // Grab CacheItem
        $cache_keyword = ($this->cache_key_creator)($keyword);
        $item = $cacheitempool->getItem($cache_keyword);


        // Stampede/Dog pile protection (proprietary)
        if ($item instanceOf StashItemInterface):
            $precompute_time = round($lifetime_value / 4);
            $item->setInvalidationMethod(StashInvalidation::PRECOMPUTE, $precompute_time);
        endif;


        // Just return cached value if valid
        if ($item->isHit()):
            $logger->log($this->loglevel_success, "Found in cache", [
                'keyword' => $keyword
            ]);
            $result = $item->get();
            return $result;
        endif;


        // Must rebuild: Create result content, using proprietary lock feature
        $logger->log($this->loglevel_success, "Not found; Content to be created.", [
            'keyword' => $keyword
        ]);

        if ($item instanceOf StashItemInterface):
            $item->lock();
        endif;


        // Rebuild + save
        $result = $content_creator($keyword);
        $item->set( $result );

        $logger->log($this->loglevel_success, "Stored in cache", [ 'lifetime' => $lifetime_value, 'keyword' => $keyword ]);
        $item->expiresAfter($lifetime_value);
        $cacheitempool->save($item);

        return $result;
    }


}

