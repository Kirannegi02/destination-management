<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get setting value by key.
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        if ($setting->type === 'json') {
            return json_decode($setting->value, true);
        }

        if ($setting->type === 'encrypted') {
            try {
                return decrypt($setting->value);
            } catch (\Exception $e) {
                // If decryption fails, return the value as-is (might be plain text)
                return $setting->value;
            }
        }

        return $setting->value ?? $default;
    }

    /**
     * Set setting value by key.
     */
    public static function set($key, $value, $type = 'text', $description = null)
    {
        $setting = self::where('key', $key)->first();

        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        if ($type === 'encrypted') {
            $value = encrypt($value);
        }

        if ($setting) {
            $setting->update([
                'value' => $value,
                'type' => $type,
                'description' => $description ?? $setting->description,
            ]);
        } else {
            self::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]);
        }
    }
}
