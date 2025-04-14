<?php

namespace App\Http\Controllers;

use App\Enums\Statuses;
use App\Models\Crop;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $field = Field::querySearch(request()->search)
            ->orderBy('created_at', 'desc')
            ->paginate(config('pagination.list'))
            ->withQueryString();

        $statuses = array_column(Statuses::cases(), 'value');

        return Inertia::render('Field/Index', [
            'fields' => $field,
            'user' => auth()->user(),
            'statuses' => $statuses,
        ]);
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
        $validate = $request->validate([
            'name' => 'required|string',
            'location' => 'nullable|string',
            'size' => 'required|numeric',
            'description' => 'nullable|string',
            'boundaries' => 'required|array',
            'status' => 'required|string',
        ]);
        $validate['user_id'] = Auth::id();

        Field::create($validate);

        return back()->with('success', 'Field created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
