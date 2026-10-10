<?php

namespace Modules\Loyalty\Enums;

enum Tier: string
{
    case Classic = 'classic';
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Diamond = 'diamond';

    /** Batas bawah tier_points. */
    public function threshold(): int
    {
        return match ($this) {
            self::Classic => 0, self::Bronze => 500, self::Silver => 1500,
            self::Gold => 7500, self::Platinum => 15000, self::Diamond => 40000,
        };
    }

    /** Bonus hanya masuk ke points_balance. */
    public function bonusRate(): float
    {
        return match ($this) {
            self::Classic, self::Bronze => 0.0, self::Silver => 0.05,
            self::Gold => 0.10, self::Platinum => 0.20, self::Diamond => 0.30,
        };
    }

    public static function forPoints(int $tierPoints): self
    {
        $result = self::Classic;
        foreach (self::cases() as $t) {
            if ($tierPoints >= $t->threshold()) {
                $result = $t;
            }
        }

        return $result;
    }

    public function rank(): int
    {
        return array_search($this, self::cases(), true);
    }

    public function oneLevelDown(): self
    {
        return self::cases()[max(0, $this->rank() - 1)];
    }

    public function next(): ?self
    {
        return self::cases()[$this->rank() + 1] ?? null;
    }
}
