<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\Admin\CategoryRequest;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $categories = $query->latest()->paginate(20);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = Category::whereNull('parent_id')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['image'] = $request->file('image_file') ? $request->file('image_file')->store('categories', 'public') : ($data['image'] ?? null);
        unset($data['image_file'], $data['remove_image']);
        Category::create($data);
        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $parents = Category::whereNull('parent_id')->where('id', '!=', $category->id)->get();
        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        $previous = $category->image;
        if ($request->file('image_file')) {
            $data['image'] = $request->file('image_file')->store('categories', 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }
        unset($data['image_file'], $data['remove_image']);
        $category->update($data);

        // Replace or remove an uploaded picture: delete the old file (never a web address).
        if ($previous && $previous !== ($data['image'] ?? null) && ! preg_match('#^(https?:)?//#i', $previous) && str_starts_with($previous, 'categories/')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($previous);
        }
        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
