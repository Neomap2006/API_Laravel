<?php

namespace App\Http\Requests;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Error',
            'errors' => $validator->errors()
        ], 422));
    }
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
       // Store
       if($this->isMethod('POST')) {
        return [
            'kode_barang' => 'required|string|unique:products,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'stok' => 'required|numeric|min:0',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'kategori' => 'required|string',
            'expired_date' => 'nullable|date',
            'rating' => 'nullable|numeric|min:0|max:5',
        ];
       }

       // Update
        return [
                'kode_barang' => [
                    'sometimes',
                    'required',
                    'string',
                    Rule::unique('products', 'kode_barang')->ignore($this->route('id')),
                ],
            'nama_barang' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'stok' => 'sometimes|numeric|min:0',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'kategori' => 'required|string',
            'expired_date' => 'nullable|date',
            'rating' => 'nullable|numeric|min:0|max:5',
        ];
    }
}
