<?php
namespace Germania\Cache;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Cache\CacheItemPoolInterface;

class CacheCallable
{

    /**
     * @var mixed
     */
    public $data = null;

    /**
     * @var LifeTimeInterface
     */
    public $lifetime = null;

    /**
     * @var CacheItemPoolInterface
     */
    public $cacheitempool = null;

    /**
     * @var callable
     */
    public $content_creator = null;

    /**
     * @var LoggerInterface
     */
    public $logger = null;



    /**
     * @param CacheItemPoolInterface $cacheitempool   PSR-6 Cache Item Pool
     * @param int|LifeTimeInterface  $lifetime        Item lifetime in seconds or LifeTime object
     * @param Callable               $content_creator Callable for content creation
     * @param LoggerInterface        $logger          Optional PSR-3 Logger; defaults to NullLogger
     */
    public function __construct(CacheItemPoolInterface $cacheitempool, $lifetime, Callable $content_creator, LoggerInterface $logger = null)
    {
        $this->cacheitempool   = $cacheitempool;
        $this->lifetime        = LifeTime::create($lifetime);
        $this->content_creator = $content_creator;
        $this->logger          = $logger ? $logger : new NullLogger;
    }


    /**
     * @param string   $keyword         Cache item identifier
     * @param Callable $content_creator Optional Callable override for content creation
     * @return mixed
     */

    public function __invoke($keyword, Callable $content_creator = null)
    {
        $lifetime        = $this->lifetime;
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
            $logger->notice("Caching disabled");

            // Remove that certain resource to avoid outdated results
            if ($cacheitempool->hasItem($keyword)) :
                $logger->debug("Delete cached item", [ 'keyword' => $keyword ]);
                $cacheitempool->deleteItem($keyword);
            else:
                $logger->debug("No cached item to delete");
            endif;

            $logger->info("Create content ...");
            $result = $content_creator();
            $logger->debug("Done.");
            return $result;
        endif;


        // Try to get from cache first:
        $cache_item = $cacheitempool->getItem($keyword);


        // If found in cache:
        if ($cache_item->isHit()):
            $logger->info("Found in cache");


        // Not found in cache:
        else:
            $logger->info("Not found; Content to be created.");

            // Create content
            $content    = $content_creator();
            $cache_item = $cache_item->set($content);

            // Store in cache if needed
            $logger->info("Store in cache", [ 'lifetime' => $lifetime_value ]);
            $cache_item->expiresAfter($lifetime_value);
            $cacheitempool->save($cache_item);

        endif;

        $result = $cache_item->get();
        $logger->debug("Done.");
        return $result;
    }


}

