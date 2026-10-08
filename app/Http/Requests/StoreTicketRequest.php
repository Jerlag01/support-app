<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'help_topic_id' => ['required', Rule::exists('help_topics', 'id')->where('is_active', true)->where('is_public', true)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'site_address' => ['nullable', 'string', 'max:255'],
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
