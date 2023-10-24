<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\News;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class NewsApiScraper extends Command
{
    protected $signature = 'app:news-api-scraper';
    protected $description = 'GET the News Data from news API, store it in the DB';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Scraping data from the News API...');

        // Make an API request to retrieve news data
        $categories = Category::all();

        $scrapedData = [];

        foreach ($categories as $category) {
            $response = $this->makeApiRequest($category);
            if (!$response->successful()) {
                $this->error("Failed to fetch data for the category: {$category->key}");
                continue;
            }

            $data = $response->json();
            $filteredData = $this->filterAndStoreData($data['articles'], $category->id);
            $scrapedData = array_merge($scrapedData, $filteredData);
        }

        $this->logScrapedData($scrapedData);
        $this->info('Scraping completed.');
    }

    private function makeApiRequest($category)
    {
        return Http::get('https://newsapi.org/v2/top-headlines', [
            'country' => 'us',
            'language' => 'en',
            'apiKey' => '7b5ef23c4c7e4903b2abf85576fcdfa4',
            'category' => $category->key,
            'pageSize' => 100, // Adjust as needed
        ]);
    }

    private function filterAndStoreData($articles, $category_id)
    {
        $filteredData = [];

        foreach ($articles as $article) {
            $source = $this->getSource($article['source']['name']);
            $existingNews = News::where('slug', $article['description'])->first();

            if (!$existingNews && !is_null($article['title']) && !is_null($article['description']) && !is_null($article['content']) && !is_null($article['urlToImage'])) {

                $news_content = $this->getFullArticleContent($article['url']);
                if($news_content){
                    $filteredData[] = [
                        'title' => $article['title'],
                        'slug' => Str::limit($article['description'], 255),
                        'category_id' => $category_id,
                        'source_id' => $source->id,
                        'source_url' => $article['url'],
                        'author' => $article['author'],
                        'description' => $news_content,
                        'published_at' => date('Y-m-d H:i:s', strtotime($article['publishedAt'])),
                        'thumbnail_url' => $article['urlToImage'],
                    ];
                    $this->storeNewsArticle(end($filteredData));
                }
            } else {
                $this->info("Skipping duplicate news article: {$article['title']}");
            }
        }

        return $filteredData;
    }

    private function getSource($sourceName)
    {
        $sourceKey = strtolower(str_replace(' ', '_', $sourceName));
        return Source::firstOrCreate(['name' => $sourceName], ['key' => $sourceKey]);
    }

    private function storeNewsArticle($articleData)
    {
        News::create($articleData);
    }

    private function logScrapedData($scrapedData)
    {
        $this->info("Total Data Count: " . count($scrapedData));
        Log::info(json_encode($scrapedData));
    }

    public function getFullArticleContent($url)
    {
        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Create a new Crawler instance and load the HTML content
                $crawler = new Crawler($html);

                // Use the Crawler to filter and extract the content of all <p> tags
                $paragraphs = $crawler->filter('p')->each(function (Crawler $node, $i) {
                    return $node->text();
                });

                // Combine the extracted paragraphs into a single string
                $articleContent = implode("\n", $paragraphs);

                // Clean up or further process the article content if needed

                return $articleContent;
            }
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Error fetching content for URL ' . $url . ': ' . $e->getMessage());
        }

        return ''; // Return an empty string if the request fails or there's an error
    }
}
