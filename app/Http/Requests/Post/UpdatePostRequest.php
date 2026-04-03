<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit content');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|file|image|max:10240',
            'featured_image_id' => 'nullable|exists:media,id',
            'status' => 'required|in:draft,published,scheduled,pending_review',
            'published_at' => 'nullable|required_if:status,scheduled|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul post wajib diisi.',
            'title.max' => 'Judul post maksimal 255 karakter.',
            'content.required' => 'Konten post wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'featured_image.image' => 'File harus berupa gambar.',
            'featured_image.max' => 'Ukuran gambar maksimal 10MB.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'published_at.required_if' => 'Tanggal publikasi wajib diisi untuk post terjadwal.',
            'published_at.date' => 'Format tanggal publikasi tidak valid.',
        ];
    }
}
