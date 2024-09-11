<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;

use App\Models\Article;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ArticleCategoriesController;


class ArticleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store($data)
    {
        try {
            // create
            $article = Article::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'url_origin' => $data['url_origin'],
                'author' => $data['author'],
                'url_thumbnail' => $data['url_thumbnail'],
                'language' => $data['language'],
                'source' => $data['source'],
                'published' => $data['published'],
            ]);

            return $article;
        } catch (\Throwable $th) {
            Log::channel('stderr')->info("Unable to save article: ".$th);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function index()
    {
        try {
            $categoriesController = new ArticleCategoriesController();

            date_default_timezone_set('America/Sao_Paulo');
            $today = date('Y-m-d');
            $articles = Article::where('published', $today)->get()->all();

            // enhance this in the future by sending to a queue
            if (empty($articles)) {
                $data = $this->getLatestArticlesFromApis();

                foreach ($data as $article) {
                    $createdArticle = $this->store($article);
                    $categoriesController->store($article['categories'], $createdArticle['id']);
                    $articleCategories = $categoriesController->getCategoriesByArticleId($createdArticle['id']);

                    $articles[] = [
                        'title' => $createdArticle->title,
                        'description' => $createdArticle->description,
                        'url_origin' => $createdArticle->url_origin,
                        'author' => $createdArticle->author,
                        'url_thumbnail' => $createdArticle->url_thumbnail,
                        'language' => $createdArticle->language,
                        'source' => $createdArticle->source,
                        'published' => $createdArticle->published,
                        "categories" => $articleCategories
                    ];
                }
            }
            
            // setting categories when articles from local db
            foreach ($articles as $key => $value) {
                if (!isset($article["categories"])) {
                    $articleCategories = $categoriesController->getCategoriesByArticleId($articles[$key]['id']);

                    $articles[$key]["categories"] = $articleCategories;
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Articles Retrieved Successfully',
                'data' => $articles
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $categoriesController = new ArticleCategoriesController();

            $article = Article::findOrFail($id);

            $articleCategories = $categoriesController->getCategoriesByArticleId($id);

            $article["categories"] = $articleCategories;

            return response()->json([
                'status' => 'success',
                'message' => 'Article Retrieved Successfully',
                'data' => $article
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getLatestArticlesFromApis() {
        try {
            // enhance this when all apis became online
            $data = $this->getArticlesFromNewsData();
            if (empty($data)) {
                $data = $this->getArticlesFromCurrents();
            }
            
            return $data;
        } catch (\Throwable $th) {
            Log::channel('stderr')->info($th);
        }
    }

    public function getArticlesFromCurrents() {
        $apiToken = $_ENV['CURRENTS_API_TOKEN'];

        if (!$apiToken) {
            Log::channel('stderr')->info('Currents API token not found!');
            return [];
        }
        
        $client = new GuzzleClient();
        $res = $client->request('GET', 'https://api.currentsapi.services/v1/latest-news?language=us&apiKey='.$apiToken);
        if ($res->getStatusCode() == 200) {
            $data = $res->getBody();
            $formattedData = [];
            // formatted data here when api is online
            return $data;
        } else {
            Log::channel('stderr')->info("Coudn't retrieve articles from Currents API");
            return [];
        }
    }

    public function getArticlesFromNewsData($date = null) {
        $apiToken = $_ENV['NEWSDATAIO_API_TOKEN'];

        if (!$apiToken) {
            Log::channel('stderr')->info('NewsData API token not found!');
            return [];
        }

        if ($date) {
            $reqUrl = 'https://newsdata.io/api/1/archive?language=en&from_date='.$date.'&to_date='.$date.'&apikey='.$apiToken;
        } else {
            $reqUrl = 'https://newsdata.io/api/1/latest?language=en&apikey='.$apiToken;
        }
        
        $client = new GuzzleClient();
        $res = $client->request('GET', $reqUrl);
        if ($res->getStatusCode() == 200) {
            $data = json_decode($res->getBody(), true);
            $formattedData = [];
            foreach ($data['results'] as $article) {
                $articleDb = [];
                $articleDb['title'] = $article['title'];
                $articleDb['description'] = $article['description'];
                $articleDb['url_origin'] = $article['link'];
                $articleDb['author'] = empty($article['creator'][0]) ? null : $article['creator'][0];
                $articleDb['url_thumbnail'] = $article['image_url'];
                $articleDb['language'] = $article['language'];
                $articleDb['source'] = $article['source_name'];
                $articleDb['published'] = $article['pubDate'];
                $articleDb['categories'] = $article['category'];
                $formattedData[] = $articleDb;
            }
            return $formattedData;
        } else {
            Log::channel('stderr')->info("Coudn't retrieve articles from NewsData API");
            return [];
        }
    }

    public function getArticleByDate(string $date) {
        try {
            $categoriesController = new ArticleCategoriesController();

            $data = $this->getArticlesFromNewsData($date);

            foreach ($data as $article) {
                $createdArticle = $this->store($article);
                $categoriesController->store($article['categories'], $createdArticle['id']);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Articles from '.$date.' now available',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
