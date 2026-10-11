<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEntryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'tag' => ['required', Rule::enum(Tag::class)],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'altitude_m' => ['nullable', 'numeric', 'between:-500,9000'],
            'recorded_at' => ['required', 'date'],
            'client_updated_at' => ['required', 'date'],
        ];
    }
}
