<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;
use DateTime;

use App\Models\Article;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ArticleCategoriesController;
use Exception;

class ArticleController extends Controller
{
    protected $categoriesController;

    public function __construct()
    {
        $this->categoriesController = new ArticleCategoriesController();
    }

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
            date_default_timezone_set('America/Sao_Paulo');
            $today = date('Y-m-d');
            $articles = Article::where('published', $today)->get()->all();

            // enhance this in the future by sending to a queue
            if (empty($articles)) {
                $articles = $this->getArticlesFromExternalApis();
            }
            
            $articles = $this->setArticleCategories($articles);
            
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

    public function getArticlesFromExternalApis($date = null)
    {
        $articles = [];

        if ($date) {
            $articles = $this->getArticlesFromCurrents($date);
        } else {
            $articles = $this->getLatestArticlesFromApis();
        }

        if (empty($articles)) throw new Exception("Couldn't retrieve older articles either from the backend or from any external API.");

        foreach ($articles as $key => $value) {
            $createdArticle = $this->store($articles[$key]);
            $this->categoriesController->store($articles[$key]['categories'], $createdArticle['id']);
            $articles[$key]['id'] = $createdArticle['id'];
        }

        return $articles;
    }

    public function setArticleCategories($articles)
    {
        foreach ($articles as $key => $value) {
            if (!isset($article["categories"])) {
                $articleCategories = $this->categoriesController->getCategoriesByArticleId($articles[$key]['id']);

                $articles[$key]["categories"] = $articleCategories;
            }
        }
        return $articles;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $article = Article::findOrFail($id);

            $articleCategories = $this->categoriesController->getCategoriesByArticleId($id);

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

    public function getLatestArticlesFromApis()
    {
        try {
            $data = $this->getArticlesFromNewsData();
            if (empty($data)) {
                $data = $this->getArticlesFromCurrents();
            }
            
            return $data;
        } catch (\Throwable $th) {
            Log::channel('stderr')->info($th);
        }
    }

    public function getArticlesFromCurrents($date = null)
    {
        $apiToken = $_ENV['CURRENTS_API_TOKEN'];

        if (!$apiToken) {
            Log::channel('stderr')->info('Currents API token not found!');
            return [];
        }

        if ($date) {
            $reqUrl = 'https://api.currentsapi.services/v1/search?language=en&start_date='.$date.'T00:00:00+00:00&end_date='.$date.'T23:59:59+00:00&apiKey='.$apiToken;
        } else {
            $reqUrl = 'https://api.currentsapi.services/v1/latest-news?language=en&apiKey='.$apiToken;
        }
        
        $client = new GuzzleClient();
        $res = $client->request('GET', $reqUrl);
        if ($res->getStatusCode() == 200) {
            Log::channel('stderr')->info(json_encode($res->getBody()));
            $data = json_decode($res->getBody(), true);
            $formattedData = [];
            foreach ($data['news'] as $article) {
                $articleDb = [];
                $articleDb['title'] = $article['title'];
                $articleDb['description'] = $article['description'];
                $articleDb['url_origin'] = $article['url'];
                $articleDb['author'] = $article['author'];
                $articleDb['url_thumbnail'] = $article['image'];
                $articleDb['language'] = $article['language'];
                $articleDb['source'] = $this->getFinalWordOfURL($article['url']);
                $articleDb['published'] = (new DateTime($article['published']))->format('Y-m-d');
                $articleDb['categories'] = $article['category'];
                $formattedData[] = $articleDb;
            }
            return $formattedData;
        } else {
            Log::channel('stderr')->info("Coudn't retrieve articles from Currents API");
            return [];
        }
    }

    public function getArticlesFromNewsData($date = null) 
    {
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

    public function getArticleByDate(string $date)
    {
        try {
            $articles = Article::where('published', $date)->get()->all();

            if (empty($articles)) {
                $articles = $this->getArticlesFromExternalApis($date);
            }

            $articles = $this->setArticleCategories($articles);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Articles from '.$date.' now available',
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
     * Extract the final word of a URL. Example: Given "https://www.maps.google.com"
     * it returns "google"
     */
    public function getFinalWordOfURL($url)
    {
        $hostname = parse_url($url, PHP_URL_HOST);
        
        $parts = explode('.', $hostname);
        
        $finalWord = $parts[count($parts) - 2];
    
        return $finalWord;
    }
}
