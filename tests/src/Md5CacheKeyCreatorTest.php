<?php
namespace tests;

use Germania\Cache\Md5CacheKeyCreator;

class Md5CacheKeyCreatorTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider provideVariousKindsOfKeys
     */
    public function testCreation( $raw_key )
    {
        $sut = new Md5CacheKeyCreator;
        $key = $sut($raw_key);

        $this->assertIsString($key);
    }


    public function provideVariousKindsOfKeys()
    {
        return array(
            [ "foo" ],
            [ 1 ],
            [ -0.99 ],
            [ new \StdClass ],
            [ new \DateTime ],
            [ array("foo" => "bar") ]
        );
    }

}
