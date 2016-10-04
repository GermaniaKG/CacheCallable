<?php
namespace Germania\Cache;

interface LifeTimeInterface {

    /**
     * @return int Seconds to expiration
     */
    public function getValue();
}
