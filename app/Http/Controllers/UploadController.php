<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class UploadController extends Controller
{
    public function create(): View
    {
        return view('files.upload', [
            'maxSizeKb' => config('files.max_size_kb'),
            'extensions' => config('files.allowed_extensions'),
            'ttlHours' => config('files.ttl_hours'),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreFileRequest $request, FileStorageService $service): JsonResponse|RedirectResponse
    {
        $file = $service->store($request->file('file'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('app.upload.uploaded'),
                'file' => $file->toPublicArray(),
            ], 201);
        }

        return redirect()->route('files.index')->with('status', __('app.upload.uploaded'));
    }
}
