<?php

declare(strict_types=1);

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;

class CreateBotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:500'],
        ];
    }
}
