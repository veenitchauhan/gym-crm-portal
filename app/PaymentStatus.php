<?php

namespace App;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
