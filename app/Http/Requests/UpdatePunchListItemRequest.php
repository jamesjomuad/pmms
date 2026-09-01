<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePunchListItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'trade' => ['nullable', 'string', 'max:100'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,critical'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['sometimes', 'string', 'in:draft,pending,in_review,approved,rejected,revision,cancelled'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
