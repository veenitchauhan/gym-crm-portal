<?php

namespace App;

enum DropdownCategory: string
{
    case MembershipPlan = 'membership_plans';
    case BillingCycle = 'billing_cycles';
    case PaymentMethod = 'payment_methods';
    case TrainerSpecialty = 'trainer_specialties';
    case SessionType = 'session_types';
    case LeadInterest = 'lead_interests';

    public function label(): string
    {
        return match ($this) {
            self::MembershipPlan => 'Membership plans',
            self::BillingCycle => 'Billing cycles',
            self::PaymentMethod => 'Payment methods',
            self::TrainerSpecialty => 'Trainer specialties',
            self::SessionType => 'Session types',
            self::LeadInterest => 'Lead interests',
        };
    }
}
