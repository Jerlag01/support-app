<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\File;

class AttachmentRules
{
    public const MAX_FILES = 5;

    public const MAX_KILOBYTES = 10 * 1024;

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => [File::types(self::EXTENSIONS)->max(self::MAX_KILOBYTES)],
        ];
    }
}
