<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body'  => 'required|string',
        ];
    }

    /**
     * Validate that X-User-Id header is present after standard rules pass.
     */
    public function passedValidation(): void
    {
        if (! $this->header('X-User-Id')) {
            abort(422, 'X-User-Id header is required');
        }
    }
}
