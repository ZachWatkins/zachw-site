<?php

namespace App\Http\Controllers;

use App\Jobs\ImportModel;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $file = $request->input('file');
        $user = auth()->user();
        if (! $user) {
            // Test mode.
            $example = User::factory()->example()->make();
            $user = User::where('name', $example->name)->first();
            $file = 'storage/app/public/import.csv';
        }
        ImportModel::dispatch(
            $file,
            Location::class,
            ['user_id' => $user->id]
        );
    }
}
