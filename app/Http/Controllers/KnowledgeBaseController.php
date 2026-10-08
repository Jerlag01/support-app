<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        return view('kb.index', [
            'search' => $search,
            'categories' => FaqCategory::public()->with('publishedFaqs')->get()->filter(fn (FaqCategory $category): bool => $category->publishedFaqs->isNotEmpty()),
            'results' => $search === null ? null : Faq::visibleToClients()->with('category')->get()
                ->filter(fn (Faq $faq): bool => str($faq->searchableText())->contains($search, ignoreCase: true))
                ->values(),
        ]);
    }

    public function category(FaqCategory $category): View
    {
        abort_unless($category->is_public, 404);

        return view('kb.category', [
            'category' => $category,
            'faqs' => $category->publishedFaqs()->get(),
        ]);
    }

    public function show(Faq $faq): View
    {
        abort_unless($faq->is_published && $faq->category->is_public, 404);

        return view('kb.show', [
            'faq' => $faq,
            'related' => $faq->category->publishedFaqs()->whereKeyNot($faq->id)->limit(5)->get(),
        ]);
    }
}
