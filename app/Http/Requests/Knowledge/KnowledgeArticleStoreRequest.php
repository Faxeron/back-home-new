<?php

namespace App\Http\Requests\Knowledge;

use Illuminate\Foundation\Http\FormRequest;
class KnowledgeArticleStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'topics' => ['nullable', 'array'],
            'topics.*.type' => ['required_with:topics', 'string', 'max:40'],
            'topics.*.name' => ['required_with:topics', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:80'],
        ];
    }
}
