<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DefaultAdminUser
{
    public const EMAIL = 'abir@admin.com';

    public const PASSWORD = 'admin';

    public static function ensureExistsIfDatabaseReady(): ?User
    {
        try {
            if (! Schema::hasTable('users')) {
                return null;
            }

            foreach (['name', 'email', 'password', 'is_admin'] as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    return null;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return self::ensureExists();
    }

    public static function ensureExists(): User
    {
        $user = User::query()->firstOrNew([
            'email' => self::EMAIL,
        ]);

        if (! $user->exists) {
            $user->name = 'Abir Admin';
            $user->password = self::PASSWORD;
        }

        if (blank($user->name)) {
            $user->name = 'Abir Admin';
        }

        if (blank($user->password)) {
            $user->password = self::PASSWORD;
        }

        $user->is_admin = true;
        $user->save();

        return $user;
    }
}
