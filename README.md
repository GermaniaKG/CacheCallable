# Germania KG · CacheCallable



[![Packagist](https://img.shields.io/packagist/v/germania-kg/cachecallable.svg?style=flat)](https://packagist.org/packages/germania-kg/cachecallable)
[![PHP version](https://img.shields.io/packagist/php-v/germania-kg/cachecallable.svg)](https://packagist.org/packages/germania-kg/cachecallable)
[![Tests](https://github.com/GermaniaKG/CacheCallable/actions/workflows/tests.yml/badge.svg)](https://github.com/GermaniaKG/CacheCallable/actions/workflows/tests.yml)


**Callable convenience wrapper around PSR-6 [Cache Item Pools](http://www.php-fig.org/psr/psr-6/#cacheitempoolinterface): Seamlessly creates, returns, and stores your data.**

Caching business is pretty much always similar and can be outlined like this: 

1. **Is caching enabled at all?**  If not, delete any according older entry first.  
	Create and return fresh content anyway, ending up here.
2.  **Does a given item exist?** If so, return item content;  
	Otherwise, create, store and return content.

**The *CacheCallable* class reduces these steps to a handy and customizable Callable.**


## Installation with Composer

```bash
$ composer require germania-kg/cachecallable
```

## Example
Although this example uses [phpfastcache](http://www.phpfastcache.com/), you should be able to pass in any [Cache Item Pool](http://www.php-fig.org/psr/psr-6/#cacheitempoolinterface). Use your favourite  [PSR-3 Logger](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#3-psrlogloggerinterface); this example will use the well-known [Monolog](https://github.com/Seldaek/monolog). 

```php
<?php
use phpFastCache\CacheManager;
use Monolog\Logger;
use Germania\Cache\CacheCallable;

// Setup dependencies
$cacheItemPool = CacheManager::getInstance('files', [ options ]);
$lifetime      = 3600;
$monolog       = new Logger( "My App" );
$content_creator = function( $keyword ) {
	return "Cache keyword: " . $keyword;
};


//
// Setup Cache wrapper
// 
$wrapped_cache = new CacheCallable(
	$cacheItemPool,
	$lifetime,
	$creator,
	$monolog // optional
);

// Identifying key. Example for a web page:
$keyword = sha1($_SERVER['REQUEST_URI']);
echo $wrapped_cache( $keyword );
```



## A word on cache keys

According to the PSR-6 specs, cache keys should be limited to `A-Z, a-z, 0-9, _, and .` to ensure maximum compatibilty. So if you pass in a PSR-6 Adapter from [Symfony Cache component](https://symfony.com/doc/current/components/cache.html), class *CacheCallable* internally converts the given keys to a MD5 representation. 

In case you'd like to provide a custom cache key creation, you may use the ***setCacheKeyCreator*** method whoch accepts any callable:

```php
$wrapped_cache->setCacheKeyCreator( function($raw) { return sha1($raw); } );
```



**PHP-FIG: [PSR-6: Caching Interface](https://www.php-fig.org/psr/psr-6/#definitions)**

> Implementing libraries MUST support keys consisting of the characters A-Z, a-z, 0-9, _, and . in any order in UTF-8 encoding and a length of up to 64 characters. Implementing libraries MAY support additional characters and encodings or longer lengths, but must support at least that minimum.

**Symfony docs: [“Cache Item Keys and Values”](https://symfony.com/doc/current/components/cache/cache_items.html#cache-item-keys-and-values)**

> The key of a cache item […] should only contain letters (A-Z, a-z), numbers (0-9) and the _ and . symbols.







## The Cache lifetime

Think of a webpage that turns out to be not cached during script runtime — *after* we set up the Cache wrapper. For this reason, the Cache wrapper constructor also accepts a **LifeTimeInterface** implementation with a *getValue* method:

```php
<?php
namespace Germania\Cache;

interface LifeTimeInterface {
	// Return seconds to expiration
	public function getValue();
}
```

The **LifeTime** **class** is a simple implementation of this interface. It additionally enables you to change the lifetime value during runtime. Since we passed it to our Cache constructor by reference, the Cache wrapper can decide *after* content creation wether to cache or not.



### Create a Lifetime object

```php
<?php
use Germania\Cache\CacheCallable;
use Germania\Cache\LifeTime;

// Setup LifeTime object
$lifetime_object = new LifeTime( 3600 );

// Use Factory method:
$lifetime_object = LifeTime::create( 3600 );

// Create from Lifetime instance
$another_lifetime = new LifeTime( $lifetime_object );
$another_lifetime = LifeTime::create( $lifetime_object );
```



### Usage with CacheCallable

```php
<?php
use Germania\Cache\CacheCallable;
use Germania\Cache\LifeTime;

// Taken from example above
$wrapped_cache = new CacheCallable(
	$cacheItemPool,
	$lifetime_object,
	$creator
);
```

Your Logger will now output something like this:

```
MyLogger DEBUG Lifetime after content creation: 0
MyLogger NOTICE DO NOT store in cache
```



### How to change lifetime during script runtime

After instantation, you may use the *setValue* method:

```php
<?php
namespace Germania\Cache;

interface LifeTimeInterface {
	// Return seconds to expiration
	public function getValue();
}
```

The **LifeTime** **class** is a simple implementation of this interface. It additionally enables you to change the lifetime value during runtime. Since we passed it to our Cache constructor by reference, the Cache wrapper can decide *after* content creation wether to cache or not.

**Create a Lifetime object:**

```php
<?php
use Germania\Cache\CacheCallable;
use Germania\Cache\LifeTime;

// Setup LifeTime object
$lifetime_object = new LifeTime( 3600 );

// Use Factory method:
$lifetime_object = LifeTime::create( 3600 );

// Create from Lifetime instance
$another_lifetime = new LifeTime( $lifetime_object );
$another_lifetime = LifeTime::create( $lifetime_object );
```

**Set time value** after instantation

```php
// Change LifeTime value during runtime, 
// e.g. in router or controller
$lifetime_object->setValue( 0 );
```

Usage with CacheCallable

```php
<?php
use Germania\Cache\CacheCallable;
use Germania\Cache\LifeTime;

// Taken from example above
$wrapped_cache = new CacheCallable(
	$cacheItemPool,
	$lifetime_object,
	$creator
);
```

Your Logger will now output something like this:

```
MyLogger DEBUG Lifetime after content creation: 0
MyLogger NOTICE DO NOT store in cache
```



## How to override content creation

If you prefer singleton services, you may *invoke* the CacheCallable with a custom content creator parameter to override the default one:

```php
// Default content creator
$default_creator = function($file) {
	return json_decode( file_get_contents($file) );
};

// Setup Service
$wrapped_cache = new CacheCallable(
    $cacheItemPool,
    $lifetime_object,
    $default_creator
);

// Override content creation 
$config = $wrapped_cache("config.json", function( $file ) {
	return array('foo' => 'bar');
};
```





## Issues

The PSR-6 Caching Interface mock in *CacheCallableTest* could need an overhaul. Discuss on [#issue 3][i3]

[i0]: https://github.com/GermaniaKG/CacheCallable/issues
[i3]: https://github.com/GermaniaKG/CacheCallable/issues/3

## Development

```bash
$ git clone https://github.com/GermaniaKG/CacheCallable.git
$ cd CacheCallable
$ composer install
```

## Unit tests

Either copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs, or leave as is. Run [PhpUnit](https://phpunit.de/) test or composer scripts like this:

```bash
$ composer test
# or
$ vendor/bin/phpunit
```




## Useful Links

- [PSR-6: Caching Interface](http://www.php-fig.org/psr/psr-6/)
- PSR-6 [CacheItemPoolInterface](http://www.php-fig.org/psr/psr-6/#cacheitempoolinterface)
- PSR-6 [CacheItemInterface](http://www.php-fig.org/psr/psr-6/#cacheiteminterface)





