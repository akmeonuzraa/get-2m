<?php

namespace App\Http\Requests;

use App\Models\SpaceMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        $spaceId = $this->integer('space_id');
        if (!$spaceId) {
            return true;
        }

        return $user->spaces()
            ->where('spaces.id', $spaceId)
            ->wherePivotIn('role', [SpaceMember::ROLE_ADMIN, SpaceMember::ROLE_CONTRIBUTOR])
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,docx,xlsx,pptx,txt,jpg,jpeg,png,gif,zip'],
            'space_id' => ['nullable', 'exists:spaces,id'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string'],
            'status' => ['nullable', Rule::in(['active', 'trashed'])],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Vous n\'êtes pas membre de cet espace.');
    }
}
