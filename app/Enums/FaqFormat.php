<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum FaqFormat: string implements HasIcon, HasLabel
{
    case RichText = 'rich';
    case Markdown = 'markdown';

    public function getLabel(): string
    {
        return __('admin.faq_formats.'.$this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::RichText => Heroicon::OutlinedDocumentText,
            self::Markdown => Heroicon::OutlinedHashtag,
        };
    }
}
