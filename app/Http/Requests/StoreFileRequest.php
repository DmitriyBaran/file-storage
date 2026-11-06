<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $extensions = implode(',', config('files.allowed_extensions'));

        return [
            'file' => [
                'required',
                'file',
                // Тип по содержимому файла (MIME через finfo), а не по присланному браузером заголовку
                'mimes:'.$extensions,
                // Расширение в имени файла — защита от «report.pdf.exe»-подобных имён
                'extensions:'.$extensions,
                // Размер в килобайтах
                'max:'.config('files.max_size_kb'),
                // Имя сохраняется в БД — длиннее колонки не пропускаем, иначе будет 500 при записи
                function (string $attribute, mixed $value, Closure $fail): void {
                    $max = config('files.max_name_length');

                    if ($value instanceof UploadedFile && mb_strlen($value->getClientOriginalName()) > $max) {
                        $fail(__('app.validation.name_too_long', ['max' => $max]));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        $maxMb = round(config('files.max_size_kb') / 1024, 1);
        $types = mb_strtoupper(implode(', ', config('files.allowed_extensions')));

        return [
            'file.required' => __('app.validation.required'),
            'file.file' => __('app.validation.file'),
            'file.uploaded' => __('app.validation.uploaded', ['size' => $maxMb]),
            'file.mimes' => __('app.validation.types', ['types' => $types]),
            'file.extensions' => __('app.validation.types', ['types' => $types]),
            'file.max' => __('app.validation.max', ['size' => $maxMb]),
        ];
    }
}
