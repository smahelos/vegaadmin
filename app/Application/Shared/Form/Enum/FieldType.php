<?php

namespace App\Application\Shared\Form\Enum;

/**
 * Enum representing supported form field types.
 */
enum FieldType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case DATE = 'date';
    case SELECT = 'select';
    case SELECT_FROM_ARRAY = 'select_from_array';
    case FILE = 'file';
    case HIDDEN = 'hidden';
    case TEXTAREA = 'textarea';

    /**
     * Gracefully map legacy string to enum, defaulting to TEXT.
     */
    public static function fromLegacy(string $value): self
    {
        return match($value) {
            'number' => self::NUMBER,
            'date' => self::DATE,
            'select' => self::SELECT,
            'select_from_array' => self::SELECT_FROM_ARRAY,
            'file' => self::FILE,
            'hidden' => self::HIDDEN,
            'textarea' => self::TEXTAREA,
            default => self::TEXT,
        };
    }
}
