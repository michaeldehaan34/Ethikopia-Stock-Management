<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk CRUD Master Barang (Bahan).
 *
 * Seluruh aturan validasi disamakan persis dengan modul Flask
 * modules/master_bahan.py (fungsi add/update):
 *   - kode  : wajib, hanya huruf kecil / angka / underscore, unik di tabel bahan
 *   - nama  : wajib, tidak boleh kosong
 *   - kategori / kelompok / satuan : dropdown (opsional, Flask memberi default
 *     bila kosong: 'Lainnya' / 'Lainnya' / 'pcs')
 *   - is_active (Status): wajib, bernilai 0 atau 1 (dropdown Aktif/Nonaktif)
 *   - urutan: opsional, numerik
 *
 * Kode dinormalisasi ke lowercase di prepareForValidation() agar pemeriksaan
 * regex dan unique berlaku pada nilai akhir (sama seperti Flask yang
 * melakukan .strip().lower() sebelum validasi).
 */
class MasterBahanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Akses sudah dijaga oleh middleware 'role:manager' di route.
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * Samakan dengan Flask: kode di-lowercase & di-trim sebelum divalidasi,
     * sehingga cek unik & regex berjalan pada nilai final.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => is_string($this->kode) ? strtolower(trim($this->kode)) : $this->kode,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Route binding parameter bernama 'id' (lihat web.php: edit/{id}).
        $id = $this->route('id');

        return [
            'kode' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                \Illuminate\Validation\Rule::unique('bahan', 'kode')->ignore($id),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'kelompok' => ['nullable', 'string', 'max:50'],
            'satuan' => ['nullable', 'string', 'max:10'],
            'urutan' => ['nullable', 'integer'],
        ];
    }

    /**
     * Pesan error dalam Bahasa Indonesia, homolog dengan modul Flask.
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode bahan tidak boleh kosong.',
            'kode.regex' => 'Kode hanya boleh huruf kecil, angka, dan underscore.',
            'kode.unique' => 'Kode bahan sudah digunakan.',
            'nama.required' => 'Nama bahan tidak boleh kosong.',
            'is_active.required' => 'Status bahan harus dipilih.',
            'is_active.in' => 'Status bahan tidak valid.',
        ];
    }
}