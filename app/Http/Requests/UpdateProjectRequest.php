<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'client_name' => ['sometimes', 'required', 'string', 'max:255'],
            'project_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'project_number')->ignore($this->route('project')),
            ],
            'awarded_date' => ['sometimes', 'required', 'date'],
            'estimated_completion_date' => ['nullable', 'date', 'after_or_equal:awarded_date'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'on_hold', 'closed'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'team' => ['nullable', 'array'],
            'team.*.user_id' => ['required_with:team', 'exists:users,id'],
            'team.*.project_role' => ['required_with:team', Rule::enum(ProjectRole::class)],
        ];
    }
}
