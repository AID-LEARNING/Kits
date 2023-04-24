<?php

namespace SenseiTarzan\Kits\Class\Kits;

class WaitingPeriod
{
    public function __construct(public string $name, public float $period)
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getPeriod(): float
    {
        return $this->period;
    }


    public function isCompleted(): bool{
        return time() >= $this->period;
    }
}