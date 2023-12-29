<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): BinaryFileResponse
    {
        $user_id = $request->input('uid');
        $filename = $request->input('file');
        $source = "user/{$user_id}/{$filename}";

        if (! Storage::exists($source)) {
            return response()->json(['error' => 'File not found:'.$request->input('file')], 404);
        }

        return response()
            ->download(Storage::path($source), $request->input('file'))
            ->deleteFileAfterSend(true);
    }
}
