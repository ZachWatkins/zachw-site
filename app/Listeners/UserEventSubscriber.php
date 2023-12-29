<?php

namespace App\Listeners;

use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

class UserEventSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Registered::class => 'registered',
            Attempting::class => 'attempting',
            Authenticated::class => 'authenticated',
            Login::class => 'login',
            Failed::class => 'failed',
            Validated::class => 'validated',
            Verified::class => 'verified',
            Logout::class => 'logout',
            CurrentDeviceLogout::class => 'currentDeviceLogout',
            OtherDeviceLogout::class => 'otherDeviceLogout',
            Lockout::class => 'lockout',
            PasswordReset::class => 'passwordReset',
        ];
    }

    public function registered(Registered $event): void
    {
        $user = User::find((int) $event->user->getAuthIdentifier());

        if (! $user) {
            throw new \Exception('User not found');
        }

        if ($user->registered_at) {
            throw new \Exception('User already registered');
        }

        $user->registered_at = now();
        $user->save();

        AuthEvent::create([
            'action' => 'registered',
            'user_id' => (int) $event->user->getAuthIdentifier(),
        ]);
    }

    public function attempting(Attempting $event): void
    {
        if (! User::where('email', $event->credentials['email'])->exists()) {
            return;
        }

        AuthEvent::create([
            'action' => 'attempting',
            'payload' => $event->credentials['email'],
        ]);
    }

    public function authenticated(Authenticated $event): void
    {
        AuthEvent::create([
            'action' => 'authenticated',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    public function login(Login $event): void
    {
        AuthEvent::create([
            'action' => 'login',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    public function failed(Failed $event): void
    {
        AuthEvent::create([
            'action' => 'failed',
            'payload' => $event->credentials['email'],
        ]);
    }

    public function validated(Validated $event): void
    {
        AuthEvent::create([
            'action' => 'validated',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    public function verified(Verified $event): void
    {
        AuthEvent::create([
            'action' => 'verified',
            'payload' => $event->user->getEmailForVerification(),
        ]);
    }

    public function logout(Logout $event): void
    {
        AuthEvent::create([
            'action' => 'logout',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    public function currentDeviceLogout(CurrentDeviceLogout $event): void
    {
        AuthEvent::create([
            'action' => 'current_device_logout',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    public function otherDeviceLogout(OtherDeviceLogout $event): void
    {
        AuthEvent::create([
            'action' => 'other_device_logout',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    public function lockout(Lockout $event): void
    {
        AuthEvent::create([
            'action' => 'lockout',
            'payload' => $event->request->email,
        ]);
    }

    public function passwordReset(PasswordReset $event): void
    {
        AuthEvent::create([
            'action' => 'password_reset',
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }
}
