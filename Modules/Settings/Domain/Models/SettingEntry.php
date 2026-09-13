<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Settings-owned persistence for runtime key/value entries.
 *
 * Internal to this module — other modules must use RuntimeSettingsContract.
 */
class SettingEntry extends Model
{
    protected $table = 'settings_entries';

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
