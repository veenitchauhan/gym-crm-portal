<?php

namespace App;

enum BookingStatus: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';
}
