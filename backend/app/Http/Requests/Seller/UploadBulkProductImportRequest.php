<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class UploadBulkProductImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'vendor_id' => ['nullable', 'integer'],
        ];
    }
}
