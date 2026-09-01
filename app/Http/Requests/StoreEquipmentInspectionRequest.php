<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentInspectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'inspected_by' => ['nullable', 'exists:users,id'],
            'inspected_date' => ['nullable', 'date'],
            'result' => ['required', 'string', 'in:passed,failed,damaged,wrong_item'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
