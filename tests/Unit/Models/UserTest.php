<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_is_admin_returns_true_only_for_admin_role(): void
    {
        $this->assertTrue((new User(['role' => 'admin']))->isAdmin());
        $this->assertFalse((new User(['role' => 'responsable']))->isAdmin());
        $this->assertFalse((new User(['role' => 'user']))->isAdmin());
    }

    public function test_is_responsable_returns_true_only_for_responsable_role(): void
    {
        $this->assertTrue((new User(['role' => 'responsable']))->isResponsable());
        $this->assertFalse((new User(['role' => 'admin']))->isResponsable());
        $this->assertFalse((new User(['role' => 'user']))->isResponsable());
    }

    public function test_fillable_attributes(): void
    {
        $expected = [
            'name', 'email', 'password', 'role', 'service',
            'avatar', 'is_active', 'last_login_at',
        ];

        $this->assertSame($expected, (new User)->getFillable());
    }

    public function test_hidden_attributes(): void
    {
        $this->assertSame(['password', 'remember_token'], (new User)->getHidden());
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $user = new User(['is_active' => 1]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    public function test_password_is_hashed_when_set(): void
    {
        $user = new User;
        $user->password = 'secret-password';

        $this->assertNotSame('secret-password', $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_datetime_attributes_are_cast_to_carbon(): void
    {
        $user = new User(['last_login_at' => '2026-01-01 10:00:00']);

        $this->assertInstanceOf(Carbon::class, $user->last_login_at);
    }

    public function test_password_and_remember_token_are_hidden_from_array(): void
    {
        $user = new User([
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ]);
        $user->password = 'secret';
        $user->remember_token = 'token';

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertSame('jane@example.com', $array['email']);
    }
}
