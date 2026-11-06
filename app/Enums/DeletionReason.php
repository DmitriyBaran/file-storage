<?php

namespace App\Enums;

enum DeletionReason: string
{
    case Manual = 'manual';
    case Expired = 'expired';

    public function label(?string $locale = null): string
    {
        return __('app.reason.'.$this->value, [], $locale);
    }
}
