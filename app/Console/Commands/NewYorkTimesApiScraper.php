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

class NewYorkTimesApiScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:new-york-times-api-scraper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GET the News Data from NewYork Times API and store it in the DB';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Scraping data from the News API...');

        // Make an API request to retrieve news data

        $response = $this->makeApiRequest();
        if (!$response->successful()) {
            $this->error("Failed to fetch data}");
            // continue;
        }

        $data = $response->json();
        $results = $data['results'];
        $filteredData = $this->filterAndStoreData($results);

        $this->logScrapedData($filteredData);
        $this->info('Scraping completed.');
    }

    private function makeApiRequest()
    {
        return Http::get(env('NEWYORK_TIMES_API_URL'), [
            'limit' => 50,
            'api-key' => env('NEWYORK_TIMES_API_KEY'),
            // Add other parameters as needed
        ]);
    }

    private function filterAndStoreData($articles)
    {
        $filteredData = [];

        foreach ($articles as $article) {
            $source = $this->getSource('The New York Times');
            $general_category_id = Category::where('key', 'general')->first()->id;
            $category_key = $article['subsection'];
            $article_category = Category::where('key', 'like', "%$category_key%")->first();
            $category_id = $article_category? $article_category->id : $general_category_id;
            $existingNews = News::withoutGlobalScope('user_preferences')->where('source_url', $article['url'])->first();

            if (!$existingNews && !is_null($article['title']) && !is_null($article['url'])) {

                $thumbnail_url = array_reduce($article['multimedia'], function ($carry, $item) {
                    return $carry ?: ($item['format'] === 'mediumThreeByTwo440' ? $item['url'] : null);
                });
                if (preg_match('/By\s+(.+)/', $article['byline'], $matches)) {
                    $authorName = $matches[1];
                } else {
                    // Handle the case where "By" is not found
                    $authorName = $article['byline'];
                }
                $filteredData[] = [
                    'title' => $article['title'],
                    'slug' => Str::limit($article['abstract'], 255),
                    'category_id' => $category_id,
                    'source_id' => $source->id,
                    'source_url' => $article['url'],
                    'author' => $authorName,
                    'description' => null,
                    'published_at' => date('Y-m-d H:i:s', strtotime($article['published_date'])),
                    'thumbnail_url' => $thumbnail_url,
                ];
                // $this->storeNewsArticle(end($filteredData));
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
}
