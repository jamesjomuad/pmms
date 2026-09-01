<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierQuotationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'supplier_name' => ['required', 'string', 'max:255'],
            'quoted_cost' => ['required', 'numeric', 'min:0'],
            'quoted_lead_time_days' => ['nullable', 'integer', 'min:1'],
            'quote_date' => ['nullable', 'date'],
        ];
    }
}
