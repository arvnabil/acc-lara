<?php

namespace Modules\Orders\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case AwaitingVerification = 'awaiting_verification';
    case Paid = 'paid';
    case Processing = 'processing';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
