<?php
namespace Germania\Cache;

class LifeTime implements LifeTimeInterface {

    public $seconds = 0;

    public function __construct( $seconds )
    {
        $this->setValue( $seconds );
    }

    public function getValue()
    {
        return $this->seconds;
    }

    public function setValue( $seconds )
    {
        $this->seconds = $seconds;
        return $this;
    }

}
