<?php

namespace Modules\Loyalty\Enums;

enum LoyaltyTier: string
{
    case Classic = 'classic';
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Diamond = 'diamond';
}
