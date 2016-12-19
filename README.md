#CacheCallable

[![Build Status](https://travis-ci.org/GermaniaKG/CacheCallable.svg?branch=master)](https://travis-ci.org/GermaniaKG/CacheCallable)
[![Code Coverage](https://scrutinizer-ci.com/g/GermaniaKG/CacheCallable/badges/coverage.png)](https://scrutinizer-ci.com/g/GermaniaKG/CacheCallable/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GermaniaKG/CacheCallable/badges/quality-score.png)](https://scrutinizer-ci.com/g/GermaniaKG/CacheCallable/)


**Callable convenience wrapper around PSR-6 [Cache Item Pools](http://www.php-fig.org/psr/psr-6/#cacheitempoolinterface): Seamlessly creates, returns, and stores your data.**

Caching business is pretty much always similar and can be outlined like this: 

1. **Is caching enabled at all?**  If not, delete any according older entry first. Create and return fresh content anyway, ending up here.
2.  **Does a given item exist?** If so, return item content; Otherwise, create, store and return content.

The CacheCallable class reduces these steps to a handy and customizable Callable.

##Installation

Use Composer:

```bash
$ composer require germania-kg/cachecallable
```


##Example
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
$content_creator = function() {
	return "My Website Content";
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
$something_unique = sha1($_SERVER['REQUEST_URI']);

// Get your data
echo $wrapped_cache( $something_unique );
```

##How to change lifetime during script runtime

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

```php
<?php
use Germania\Cache\ CacheCallable;
use Germania\Cache\LifeTime;

// Setup LifeTime object
$lifetime_object = new LifeTime( 3600 );

// Taken from example above
$wrapped_cache = new CacheCallable(
	$cacheItemPool,
	$lifetime_object,
	$creator
);

// Change LifeTime value during runtime, 
// e.g. in router or controller
$lifetime_object->setValue( 0 );
```

Your Logger will now output something like this:

```
MyLogger DEBUG Lifetime after content creation: 0
MyLogger NOTICE DO NOT store in cache
```

##How to override content creation

If you prefer singleton services, you may *invoke* the CacheCallable with a custom content creator parameter to override the default one:

```php
// The default content creator for web page HTML
$default_creator = function() {
	// Assuming Twig
	return $template->render( ... );
};

// Setup Service
$wrapped_cache = new CacheCallable(
    $cacheItemPool,
    $lifetime_object,
    $default_creator
);

// Custom data creation for configuration
$config = $wrapped_cache("my-config", function() {
	return json_decode( file_get_contents("config.json") );
};

// Identify web page, using SHA1 hash:
$something_unique = sha1($_SERVER['REQUEST_URI']);
$page_html = $wrapped_cache( $something_unique );
```


##Development and testing

Clone repo, use [Git Flow](https://github.com/nvie/gitflow). Work on *develop* branch.

```bash
# Clone Repo
$ git clone git@github.com:GermaniaKG/CacheCallable.git germania-cachecallable
$ cd germania-cachecallable
$ composer install
```

For testing, copy PHPUnit configuration file and customize if needed.

```bash
$ cp phpunit.xml.dist phpunit.xml
$ phpunit
```

###TODO

**Create better abstractions of PSR-6 interfaces:** The *tests\CacheCallableTest* currently use a quick and dirty implementation of 
the [PSR-6: Caching Interface](http://www.php-fig.org/psr/psr-6/) interfaces. See *mocks* namespace in *tests* directory.




##Useful Links

- [PSR-6: Caching Interface](http://www.php-fig.org/psr/psr-6/)
- PSR-6 [CacheItemPoolInterface](http://www.php-fig.org/psr/psr-6/#cacheitempoolinterface)
- PSR-6 [CacheItemInterface](http://www.php-fig.org/psr/psr-6/#cacheiteminterface)





