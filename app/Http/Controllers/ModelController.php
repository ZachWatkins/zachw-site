<?php

namespace App\Http\Controllers;

use App\Models\Model;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        return response()->json(['models' => $user->models]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Model::create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();

        return Model::where('user_id', auth()->user()->id)->where('id', (int) $id)->first();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $model = Model::where('user_id', auth()->user()->id)->where('id', (int) $id)->first();
        if (! $model) {
            return response()->json(['message' => 'Model not found'], 404);
        }
        // Sanitize.
        $attributes = $request->all();
        unset($attributes['user_id']);

        $model->fill($attributes);
        if (! $model->save()) {
            return response()->json(['message' => 'Model not saved'], 500);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Model::destroy(intval($id));
    }
}
