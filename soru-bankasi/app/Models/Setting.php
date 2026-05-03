<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value_type',
        'value',
    ];

    public function getTypedValueAttribute(): mixed
    {
        return self::decodeValue($this->value_type, $this->value);
    }

    public function setTypedValue(mixed $value): void
    {
        $this->value_type = self::determineValueType($value);
        $this->value = self::encodeValue($value);
    }

    public static function determineValueType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    public static function encodeValue(mixed $value): string
    {
        return match (self::determineValueType($value)) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]',
            default => (string) $value,
        };
    }

    public static function decodeValue(?string $valueType, ?string $value): mixed
    {
        return match ($valueType) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $value,
            'json' => $value ? json_decode($value, true) : [],
            default => $value,
        };
    }
}
