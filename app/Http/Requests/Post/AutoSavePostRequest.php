<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class AutoSavePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can create content (for new posts) or edit content (for existing posts)
        if ($this->route('post')) {
            return $this->user()->can('edit content');
        }
        return $this->user()->can('create content');
    }

    /**
     * Get the validation rules that apply to the request.
     * Validation rules are more lenient for auto-save since content may be incomplete.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'featured_image_id' => 'nullable|exists:media,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }
}
