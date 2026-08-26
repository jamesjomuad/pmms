<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'project_number' => ['nullable', 'string', 'max:50', 'unique:projects,project_number'],
            'awarded_date' => ['required', 'date'],
            'estimated_completion_date' => ['nullable', 'date', 'after_or_equal:awarded_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'team' => ['nullable', 'array'],
            'team.*.user_id' => ['required_with:team', 'exists:users,id'],
            'team.*.project_role' => ['required_with:team', Rule::enum(ProjectRole::class)],
        ];
    }
}
