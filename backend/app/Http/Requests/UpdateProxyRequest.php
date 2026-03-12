<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProxyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'host'     => ['sometimes', 'required', 'string', 'max:255'],
            'port'     => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['sometimes', 'required', 'in:http,https,socks4,socks5'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}

