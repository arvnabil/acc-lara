<?php

namespace Modules\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;

class OrderReturned
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
