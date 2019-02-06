<?php
namespace tests;

use Germania\Cache\LifeTime;


class LifeTimeTest extends \PHPUnit\Framework\TestCase
{

    public function testGetter() {
        $sut = new LifeTime( 1 );
        $this->assertEquals( 1, $sut->getValue() );
    }


    public function testFluidInterface() {
        $sut = new LifeTime( 1 );

        $new = 2;
        $this->assertSame( $sut, $sut->setValue( $new ) );
    }

    public function testSetter() {
        $sut = new LifeTime( 1 );

        $new = 2;
        $this->assertEquals( 2, $sut->setValue( $new )->getValue() );
    }




}
