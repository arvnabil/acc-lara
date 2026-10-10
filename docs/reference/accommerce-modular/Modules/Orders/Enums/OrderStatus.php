<?php

namespace Modules\Orders\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case AwaitingVerification = 'awaiting_verification';   // klik "Saya Sudah Bayar", BUKAN lunas
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
