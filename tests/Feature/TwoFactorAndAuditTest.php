<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('redirects to two-factor challenge when 2FA is enabled', function () {
    $g = new Google2FA;
    $secret = $g->generateSecretKey();

    $user = User::factory()->create();
    $user->assignRole('Admin');
    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [],
    ])->save();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

it('completes login after valid TOTP', function () {
    $g = new Google2FA;
    $secret = $g->generateSecretKey();

    $user = User::factory()->create();
    $user->assignRole('Admin');
    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [],
    ])->save();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $otp = $g->getCurrentOtp($secret);

    $this->post(route('two-factor.verify'), [
        'code' => $otp,
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('forbids audit log without permission', function () {
    $user = User::factory()->create();
    $user->assignRole('Author');

    $this->actingAs($user)->get(route('admin.audit-log.index'))->assertForbidden();
});

it('allows audit log with permission', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'login',
        'subject_type' => null,
        'subject_id' => null,
        'event' => null,
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'properties' => [],
        'attribute_changes' => null,
    ]);

    $this->actingAs($user)->get(route('admin.audit-log.index'))->assertOk();
});
