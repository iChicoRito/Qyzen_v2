<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'tbl_system_settings';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function offlineRegistrationEnabled(): bool
    {
        $setting = static::where('key', 'offline_registration_enabled')->first();

        return (bool) ($setting?->value['enabled'] ?? false);
    }

    public static function setOfflineRegistrationEnabled(bool $enabled): void
    {
        static::updateOrCreate(
            ['key' => 'offline_registration_enabled'],
            ['value' => ['enabled' => $enabled]],
        );
    }
}
