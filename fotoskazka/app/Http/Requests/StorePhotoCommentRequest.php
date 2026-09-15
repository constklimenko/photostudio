<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class StorePhotoCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Comment::class, $this->route('photo')]) ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
