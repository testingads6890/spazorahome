<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomIdea;
use App\Http\Requests\Admin\RoomIdeaRequest;
use Illuminate\Http\Request;

class RoomIdeaController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomIdea::query();
        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }
        $roomIdeas = $query->latest()->paginate(20);
        return view('admin.room-ideas.index', compact('roomIdeas'));
    }

    public function create()
    {
        return view('admin.room-ideas.create');
    }

    public function store(RoomIdeaRequest $request)
    {
        RoomIdea::create($request->validated());
        return redirect()->route('admin.room-ideas.index')->with('success', 'Room idea created successfully.');
    }

    public function show(RoomIdea $roomIdea)
    {
        return view('admin.room-ideas.show', compact('roomIdea'));
    }

    public function edit(RoomIdea $roomIdea)
    {
        return view('admin.room-ideas.edit', compact('roomIdea'));
    }

    public function update(RoomIdeaRequest $request, RoomIdea $roomIdea)
    {
        $roomIdea->update($request->validated());
        return redirect()->route('admin.room-ideas.index')->with('success', 'Room idea updated successfully.');
    }

    public function destroy(RoomIdea $roomIdea)
    {
        $roomIdea->delete();
        return redirect()->route('admin.room-ideas.index')->with('success', 'Room idea deleted successfully.');
    }
}
