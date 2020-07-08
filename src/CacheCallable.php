<?php
namespace Germania\Cache;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Psr\Cache\CacheItemPoolInterface;

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
     * PSR-3 Loglevel name
     * @var string
     */
    public $loglevel_success = "info";



    /**
     * @param CacheItemPoolInterface $cacheitempool   PSR-6 Cache Item Pool
     * @param int|LifeTimeInterface  $lifetime        Item's default lifetime in seconds or LifeTime object
     * @param Callable               $content_creator Callable for content creation
     * @param LoggerInterface        $logger          Optional PSR-3 Logger; defaults to NullLogger
     */
    public function __construct(CacheItemPoolInterface $cacheitempool, $lifetime, callable $content_creator, LoggerInterface $logger = null)
    {
        $this->cacheitempool    = $cacheitempool;
        $this->default_lifetime = LifeTime::create($lifetime);
        $this->content_creator  = $content_creator;
        $this->setLogger( $logger ?: new NullLogger);
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
            'content_creator' => $content_creator_type
        ]);


        $lifetime_value = $lifetime->getValue();

        if ($lifetime_value > 0) :
            $logger->debug("Caching enabled", [ 'lifetime' => $lifetime_value ]);
        else:
            $logger->debug("Caching disabled");

            // Remove that certain resource to avoid outdated results
            if ($cacheitempool->hasItem($keyword)) :
                $logger->debug("Delete cached item", [ 'keyword' => $keyword ]);
                $cacheitempool->deleteItem($keyword);
            else:
                $logger->debug("No cached item to delete");
            endif;

            $logger->log($this->loglevel_success, "Create content ...");
            $result = $content_creator();
            $logger->debug("Done.");
            return $result;
        endif;


        // Try to get from cache first:
        $cache_item = $cacheitempool->getItem($keyword);


        // If found in cache:
        if ($cache_item->isHit()):
            $logger->log($this->loglevel_success, "Found in cache");
            $result = $cache_item->get();
            $logger->debug("Done.");
            return $result;
        endif;


        // Not found in cache, store.
        $logger->log($this->loglevel_success, "Not found; Content to be created.");

        // Create result content
        $result    = $content_creator();
        $cache_item = $cache_item->set($result);

        $logger->log($this->loglevel_success, "Stored in cache", [ 'lifetime' => $lifetime_value ]);
        $cache_item->expiresAfter($lifetime_value);
        $cacheitempool->save($cache_item);

        $logger->debug("Done.");
        return $result;
    }


}

