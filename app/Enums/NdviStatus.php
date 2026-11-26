<?php

namespace App\Enums;

enum NdviStatus: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case NoData = 'no_data';

    public static function fromAverage(?float $averageNdvi): self
    {
        return match (true) {
            $averageNdvi === null => self::NoData,
            $averageNdvi >= 0.7 => self::Excellent,
            $averageNdvi >= 0.5 => self::Good,
            $averageNdvi >= 0.3 => self::Fair,
            default => self::Poor,
        };
    }

    public function isHealthy(): bool
    {
        return in_array($this, [self::Excellent, self::Good], true);
    }
}
