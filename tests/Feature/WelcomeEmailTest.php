<?php

namespace Tests\Feature;

use App\Mail\ClientWelcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registered_client_receives_a_welcome_email(): void
    {
        Mail::fake();

        $this->post('/register', [
            'gym_name' => 'North Star Fitness',
            'name' => 'Priya Admin',
            'email' => 'priya@example.test',
            'phone' => '9999999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        Mail::assertSent(ClientWelcome::class, function (ClientWelcome $mail): bool {
            return $mail->hasTo('priya@example.test')
                && $mail->gym->name === 'North Star Fitness'
                && $mail->actionLabel === 'Sign in to your workspace'
                && $mail->actionUrl === route('login');
        });
    }

    public function test_super_admin_created_client_receives_a_secure_password_setup_email(): void
    {
        Mail::fake();

        $this->withSession(['super_admin_authenticated' => true])->post('/super-admin/gyms', [
            'name' => 'Pulse Fitness',
            'email' => 'owner@pulse.test',
            'phone' => '+91 99999 99999',
            'subscription_plan' => 'Growth',
            'subscription_status' => 'active',
            'subscription_expires_at' => '2027-08-29',
            'monthly_fee' => 4999,
            'payment_status' => 'paid',
            'administrator_name' => 'Priya Sharma',
            'administrator_email' => 'priya@pulse.test',
            'administrator_password' => 'temporary-password',
            'administrator_password_confirmation' => 'temporary-password',
        ])->assertRedirect();

        Mail::assertSent(ClientWelcome::class, function (ClientWelcome $mail): bool {
            return $mail->hasTo('priya@pulse.test')
                && $mail->actionLabel === 'Set your password'
                && str_contains($mail->actionUrl, '/reset-password/')
                && str_contains($mail->actionUrl, 'email=priya%40pulse.test');
        });
    }

    public function test_welcome_email_does_not_include_a_password(): void
    {
        Mail::fake();

        $this->withSession(['super_admin_authenticated' => true])->post('/super-admin/gyms', [
            'name' => 'Pulse Fitness',
            'email' => 'owner@pulse.test',
            'subscription_plan' => 'Growth',
            'subscription_status' => 'active',
            'monthly_fee' => 4999,
            'payment_status' => 'paid',
            'administrator_name' => 'Priya Sharma',
            'administrator_email' => 'priya@pulse.test',
            'administrator_password' => 'never-email-this-password',
            'administrator_password_confirmation' => 'never-email-this-password',
        ]);

        Mail::assertSent(ClientWelcome::class, function (ClientWelcome $mail): bool {
            $html = $mail->render();

            return ! str_contains($html, 'never-email-this-password')
                && str_contains($html, 'priya@pulse.test');
        });
    }
}
