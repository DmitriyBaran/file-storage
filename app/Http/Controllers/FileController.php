<?php

namespace App\Http\Controllers;

use App\Enums\DeletionReason;
use App\Models\StoredFile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function index(): View
    {
        return view('files.index', [
            'files' => StoredFile::query()->latest()->latest('id')->paginate(15),
        ]);
    }

    public function download(StoredFile $file): StreamedResponse
    {
        // Срок вышел, но планировщик ещё не успел удалить файл (он ходит раз в минуту)
        abort_if($file->isExpired(), 410, __('app.errors.expired'));

        $disk = Storage::disk($file->disk);

        abort_unless($disk->exists($file->path), 404, __('app.errors.not_on_disk'));

        return $disk->download($file->path, $file->original_name);
    }

    public function destroy(Request $request, StoredFile $file, FileStorageService $service): JsonResponse|RedirectResponse
    {
        $service->delete($file, DeletionReason::Manual);

        $message = __('app.files.deleted', ['name' => $file->original_name]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('files.index')->with('status', $message);
    }
}
