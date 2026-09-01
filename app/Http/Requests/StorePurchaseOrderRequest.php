<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'supplier_quotation_id' => ['nullable', 'exists:supplier_quotations,id'],
            'po_number' => ['required', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'expected_delivery_date' => ['nullable', 'date'],
            'actual_delivery_date' => ['nullable', 'date'],
        ];
    }
}
