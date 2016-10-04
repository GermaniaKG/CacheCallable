<?php
namespace tests;

use Germania\Cache\LifeTime;
use Germania\Cache\LifeTimeInterface;
use Germania\Cache\CacheCallable;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;

use Prophecy\Argument;

use mocks\CacheItemPoolMock;
use mocks\CacheItemMock;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class CacheCallableTest extends \PHPUnit_Framework_TestCase
{

    public $predefined_creator_content = 'predefined_content';


    /**
     * @dataProvider provideDefaults
     */
    public function testSimpleInstantiation( $empty_item_pool_mock, $lifetime, $callable_mock, $logger_mock) {
        $sut = new CacheCallable(
            $empty_item_pool_mock,
            $lifetime,
            $callable_mock,
            $logger_mock
        );

        // Code smell: Check member vars
        $this->assertInstanceOf('Germania\Cache\LifeTimeInterface', $sut->lifetime);
        $this->assertInstanceOf('Psr\Log\LoggerInterface',          $sut->logger);

        // Check content will be created
        $this->assertEquals( $callable_mock(), $sut("any_key_here"));
    }


    /**
     * @dataProvider provideDefaults
     */
    public function testExistingCacheItem( $empty_item_pool_mock, $lifetime, $callable_mock, $logger_mock) {
        // CacheItem->isHit() => true
        $item = new CacheItemMock("foo", "bar", true);
        $empty_item_pool_mock->save( $item );

        $sut = new CacheCallable(
            $empty_item_pool_mock,
            $lifetime,
            $callable_mock,
            $logger_mock
        );
        $sut->lifetime->setValue( 1 );
        $this->assertEquals( $item->get(), $sut("foo"));
    }



    /**
     * @dataProvider provideDefaults
     */
    public function testExpiredCacheItem( $empty_item_pool_mock, $lifetime, $callable_mock, $logger_mock) {
        // CacheItem->isHit() => false
        $item = new CacheItemMock("foo", "bar", false);
        $empty_item_pool_mock->save( $item );

        $sut = new CacheCallable(
            $empty_item_pool_mock,
            $lifetime,
            $callable_mock,
            $logger_mock
        );
        $sut->lifetime->setValue( 1 );
        $this->assertEquals( $this->predefined_creator_content, $sut("foo"));
    }



    /**
     * @dataProvider provideDefaults
     */
    public function testNoLifeTimeCacheItem( $empty_item_pool_mock, $lifetime, $callable_mock, $logger_mock) {
        // CacheItem->isHit() => false
        $item = new CacheItemMock("foo", "bar", false);
        $empty_item_pool_mock->save( $item );


        $no_lifetime_object = $this->prophesize( LifeTime::class );
        $no_lifetime_object->getValue()->willReturn(0);
        $no_lifetime_object->setValue(Argument::any())->will(function () {});
        $no_lifetime_object_revealed = $no_lifetime_object->reveal();

        $sut = new CacheCallable(
            $empty_item_pool_mock,
            $no_lifetime_object_revealed,
            $callable_mock,
            $logger_mock
        );

        $this->assertEquals( $this->predefined_creator_content, $sut("foo"));
        $this->assertEquals( $this->predefined_creator_content, $sut("not_in_pool"));
    }


    /**
     * @dataProvider provideDefaults
     */
    public function testOverrideLifeTime( $empty_item_pool_mock, $lifetime, $callable_mock, $logger_mock) {
        $default_lifetime = new LifeTime( 1 );

        $sut = new CacheCallable(
            $empty_item_pool_mock,
            $default_lifetime,
            function() use ($default_lifetime) {
                $default_lifetime->setValue( 0 );
                return $this->predefined_creator_content;
            },
            $logger_mock
        );

        $this->assertEquals( $this->predefined_creator_content, $sut("foo"));
    }




    /**
     * @dataProvider provideDefaults
     */
    public function testCustomContentCreator( $empty_item_pool_mock, $lifetime, $callable_mock, $logger_mock) {
        // CacheItem->isHit() => false
        $item = new CacheItemMock("foo", "bar", false);
        $empty_item_pool_mock->save( $item );

        $sut = new CacheCallable(
            $empty_item_pool_mock,
            $lifetime,
            $callable_mock,
            $logger_mock
        );

        $custom_content = "bar";
        $custom_creator = function() use ($custom_content) {
            return $custom_content;
        };

        $this->assertEquals( $custom_content, $sut("foo", $custom_creator));
    }





    public function provideDefaults()
    {
        $empty_item_pool_mock  = new CacheItemPoolMock;

        $lifetime_int    = 1;

        $lifetime_object = $this->prophesize( LifeTime::class );
        $lifetime_object->getValue()->willReturn(2);
        $lifetime_object->setValue(Argument::any())->will(function () {});
        $lifetime_object_revealed = $lifetime_object->reveal();


        $logger_mock = $this->createMock('Psr\Log\LoggerInterface');
        $logger_mock = new Logger("CacheCallable Test", [
            new StreamHandler('php://stdout', 0)
        ]);

        $creator_mock    =  function () {
            return $this->predefined_creator_content;
        };

        return array(
            [ $empty_item_pool_mock, $lifetime_int, $creator_mock, $logger_mock],
            [ $empty_item_pool_mock, $lifetime_int, $creator_mock, null],
            [ $empty_item_pool_mock, $lifetime_object_revealed, $creator_mock, $logger_mock],
            [ $empty_item_pool_mock, $lifetime_object_revealed, $creator_mock, null]
        );

    }

}
