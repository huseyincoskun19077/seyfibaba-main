<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadSellerKycDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(\App\Services\SellerIyzicoOnboardingService::UPLOADABLE_DOCUMENT_TYPES)],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'iban' => ['nullable', 'string', 'max:34'],
            'tax_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.in' => 'Şu an yalnızca vergi levhası yükleyebilirsiniz.',
        ];
    }
}
