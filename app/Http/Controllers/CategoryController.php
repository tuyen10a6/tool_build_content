<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'categories' => Category::query()
                ->withCount([
                    'contents' => fn ($query) => $query->visibleTo($this->user()),
                ])
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Category::create($validated);

        return back()->with('status', 'Tạo danh mục thành công.');
    }

    public function show(Category $category)
    {
        $category->load([
            'contents' => fn ($query) => $query->visibleTo($this->user())->withCount('scenes'),
        ]);

        return view('categories.show', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.show', $category)->with('status', 'Cập nhật danh mục thành công.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Đã xóa danh mục và dữ liệu liên quan.');
    }
}
