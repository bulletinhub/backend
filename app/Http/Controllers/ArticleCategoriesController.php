<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\ArticlesCategories;

class ArticleCategoriesController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store($data, $articleId)
    {
        try {
            foreach ($data as $category) {
                ArticlesCategories::create([
                    'id_articles' => $articleId,
                    'name' => trim($category),
                ]);
            }

        } catch (\Throwable $th) {
            Log::channel('stderr')->info("Unable to save article: ".$th);
        }
    }

    public function getCategoriesByArticleId($articleId) {
        try {
            $data = ArticlesCategories::where('id_articles', $articleId)->get()->all();

            return $data;
        } catch (\Throwable $th) {
            Log::channel('stderr')->info("Unable to retrieve article categories: ".$th);
        }
    }

}
