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

class GuardianApiScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:guardian-api-scraper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GET the News Data from Guardian API and store it in the DB';

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
        }

        $data = $response->json();
        $results = $data['response']['results'];
        $filteredData = $this->filterAndStoreData($results);

        $this->logScrapedData($filteredData);
        $this->info('Scraping completed.');
    }

    private function makeApiRequest()
    {
        return Http::get(env('GUARDIAN_API_URL'), [
            'page-size' => 100,
            'api-key' => env('GUARDIAN_API_KEY'),
        ]);
    }

    private function filterAndStoreData($articles)
    {
        $filteredData = [];

        foreach ($articles as $article) {
            $source = $this->getSource('The Guardian');
            $general_category_id = Category::where('key', 'general')->first()->id;
            $category_key = $article['sectionId'];
            $article_category = Category::where('key', 'like', "%$category_key%")->first();
            $category_id = $article_category? $article_category->id : $general_category_id;
            $existingNews = News::withoutGlobalScope('user_preferences')->where('source_url', $article['webUrl'])->first();

            if (!$existingNews && !is_null($article['webTitle']) && !is_null($article['webUrl'])) {

                $news_content = $this->getFullArticleContent($article['webUrl']);
                if($news_content){
                    $filteredData[] = [
                        'title' => $article['webTitle'],
                        'slug' => Str::limit($news_content, 255),
                        'category_id' => $category_id,
                        'source_id' => $source->id,
                        'source_url' => $article['webUrl'],
                        'author' => null,
                        'description' => $news_content,
                        'published_at' => date('Y-m-d H:i:s', strtotime($article['webPublicationDate'])),
                        'thumbnail_url' => null,
                    ];
                    $this->storeNewsArticle(end($filteredData));
                }
            } else {
                $this->info("Skipping duplicate news article: {$article['webTitle']}");
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

    private function getFullArticleContent($url)
    {
        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $html = $response->body();

                $crawler = new Crawler($html);

                $paragraphs = $crawler->filter('p')->each(function (Crawler $node, $i) {
                    return $node->text();
                });

                $articleContent = implode("\n", $paragraphs);


                return $articleContent;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching content for URL ' . $url . ': ' . $e->getMessage());
        }

        return ''; // Return an empty string if the request fails or there's an error
    }
}
