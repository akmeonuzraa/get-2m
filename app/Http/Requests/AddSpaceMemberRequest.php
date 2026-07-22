<?php

namespace App\Http\Requests;

use App\Models\SpaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddSpaceMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in(SpaceMember::ROLES)],
        ];
    }
}
