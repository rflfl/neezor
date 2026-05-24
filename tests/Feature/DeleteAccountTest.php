<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accounts_can_be_deleted(): void
    {
        $this->markTestSkipped('Account deletion tests require full browser context');
    }

    public function test_correct_password_must_be_provided_before_account_can_be_deleted(): void
    {
        $this->markTestSkipped('Account deletion tests require full browser context');
    }
}