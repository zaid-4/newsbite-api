<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\News;
use App\Models\Source;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::query();

        // Filtering by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filtering by source
        if ($request->has('source_id')) {
            $query->where('author', $request->input('source_id'));
        }

        if ($request->has('author_name')) {
            $authorName = $request->input('author_name');
            $query->where('author', 'like', "%$authorName%");
        }

        // Filtering by keyword
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%$keyword%")
                    ->orWhere('description', 'like', "%$keyword%")
                    ->orWhere('author', 'like', "%$keyword%");
            });
        }

        // Sorting by date
        if ($request->has('sort') && $request->input('sort') === 'asc') {
            $query->orderBy('published_at', 'asc');
        } else {
            $query->orderBy('published_at', 'desc');
        }

        $news = $query->paginate(15);

        $news->transform(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'description' => $item->description,
                'author' => $item->author,
                'source_url' => $item->source_url,
                'thumbnail_url' => $item->thumbnail_url,
                'title' => $item->title,
                'published_at' => $item->published_at,
                'category' => $item->category->name, // Use the actual field name of the category name
                'source' => $item->source->name, // Use the actual field name of the source name
            ];
        });

        return response()->json($news);
    }

    public function show($id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json(['message' => 'News not found'], 404); // HTTP 404 Not Found
        }

        $newsDetail = [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'description' => $news->description,
            'author' => $news->author,
            'source_url' => $news->source_url,
            'thumbnail_url' => $news->thumbnail_url,
            'published_at' => $news->published_at,
            'category' => $news->category->name, // Use the actual field name of the category name
            'source' => $news->source->name, // Use the actual field name of the source name
        ];

        return response()->json(['news' => $newsDetail], 200);
    }

    public function getAllCategoriesAndSources()
    {
        // Fetch categories and sources that have related news articles
        $categories = Category::has('news')->get();
        $sources = Source::has('news')->get();

        // Return the filtered categories and sources as JSON
        return response()->json([
            'categories' => $categories,
            'sources' => $sources,
        ]);
    }
}
