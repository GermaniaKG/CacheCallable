<?php
namespace tests;

use Germania\Cache\LifeTime;
use Germania\Cache\LifeTimeInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class LifeTimeTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public function testGetter() {
        $value = 22;
        $sut = new LifeTime( $value );
        $this->assertEquals( $value, $sut->getValue() );
    }


    /**
     * @dataProvider provideValues
     */
    public function testFactoryMethos($value) {
        $sut = LifeTime::create( $value );
        $this->assertInstanceOf( LifeTime::class, $sut );
    }


    /**
     * @dataProvider provideValues
     */
    public function testFluidInterface($value) {
        $sut = new LifeTime( 1 );

        $new = $sut->getValue() + 1;
        $this->assertSame( $sut, $sut->setValue( $new ) );
    }


    /**
     * @dataProvider provideValues
     */
    public function testSetter( $value ) {
        $sut = new LifeTime( $value  );

        $new = $sut->getValue() + 1;
        $this->assertEquals( $new, $sut->setValue( $new )->getValue() );
    }


    public function provideValues()
    {
        $LT_mock = $this->prophesize( LifeTimeInterface::class );
        $LT_mock->getValue()->willReturn( 42) ;

        return array(
            [ 1 ],
            [ $LT_mock->reveal() ],
        );
    }

}
