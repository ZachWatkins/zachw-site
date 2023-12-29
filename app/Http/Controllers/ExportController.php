<?php

namespace App\Http\Controllers;

use App\Jobs\ExportUserModels;
use App\Jobs\ZipUserFiles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    /**
     * List available exports.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        return array_map(fn ($file) => Url::temporarySignedRoute(
            'download',
            now()->addDay(),
            ['uid' => $user->id, 'file' => basename($file)]
        ), glob(Storage::path("exports/{$user->id}/*")));
    }

    /**
     * Handle the incoming request.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $now = now();
        $date = $now->format('Y-m-d');
        $model = '\\App\\Models\\'.Str::studly($request->input('model', 'location'));
        $select = array_filter(explode(',', $request->input('keys', '')));
        $where = $this->whereModelUser($request, $user);
        $csv_dest = $date.'/'.$request->input('model', 'location').'.csv';
        $zip_dest = $request->input('model', 'location').'.zip';

        // Export the model to a CSV file.
        ExportUserModels::dispatchSync($user->id, $csv_dest, $model, $select, $where);

        // Archive all files in the user's folder.
        ZipUserFiles::dispatchSync($user->id, $zip_dest, $csv_dest);

        // Create a signed route for authentication.
        $url = URL::temporarySignedRoute(
            'download',
            $now->clone()->addDays(3),
            [
                'uid' => $user->id,
                'file' => $request->input('model', 'location').'.zip',
            ]
        );

        return response()->json(['link' => $url]);
    }

    /**
     * Return a where clause scoped to the given user.
     *
     * @param  Request  $request Current Request object.
     * @param  User  $user    User scope.
     */
    protected function whereModelUser(Request $request, User $user): array
    {
        switch ($request->input('model')) {
            case 'location':
                return ['user_id' => $user->id];
            default:
                return [];
        }
    }
}
