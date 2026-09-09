<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'commentable_type' => ['required', Rule::in(['shop-drawing', 'submittal', 'rfi', 'change-order', 'punch-list-item', 'equipment'])],
            'commentable_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:4000'],
        ];
    }
}
