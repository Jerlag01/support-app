<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            ...AttachmentRules::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('support.fields');
    }
}
