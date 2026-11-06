<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Запоминает выбранный язык и возвращает на страницу, с которой пришли.
     * Допустимые значения ограничены в маршруте.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put(SetLocale::SESSION_KEY, $locale);

        return redirect()->back(fallback: route('upload.create'));
    }
}
