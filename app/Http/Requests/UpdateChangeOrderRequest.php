<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChangeOrderRequest extends FormRequest
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
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'cost_impact' => ['sometimes', 'required', 'numeric'],
            'schedule_impact_days' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:draft,pending,in_review,approved,rejected,revision,cancelled'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
