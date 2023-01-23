<?php

namespace App\Services;

use App\Helpers\ScrapedPage;
use DOMDocument;
use Illuminate\Support\Collection;

class WebCrawlerService
{
    protected string $url;
    protected string $baseUrl;
    protected int $numPages;

    public function __construct(string $url, int $numPages)
    {
        $this->url = $url;
        $this->numPages = $numPages;
    }

    /**
     * Using the URL as an entry point, scrape the requested number of pages.
     *
     * @return Collection $scrapedPages Contains scraped page data
     */
    public function scrapeDomain(): Collection
    {
        $scrapedPages = [];

        // Use the requested URL as an entry point to gather additional internal links.
        $urlResult = parse_url($this->url);
        $this->baseUrl = $urlResult['scheme']."://".$urlResult['host'];
        $scrapedEntryPage = $this->scrapePage($this->baseUrl, $this->url);

        // Record page as scraped.
        $scrapedPages[$scrapedEntryPage->getUrl()] = $scrapedEntryPage;
        // We will use the internal links to crawl more of the site.
        $internalLinks = $scrapedEntryPage->getInternalLinks();

        // The # of internal links may not be enough to accomodate the number of pages requested.
        $numPagesToProcess = count($internalLinks) < $this->numPages
            ? count($internalLinks) : $this->numPages;

        // Navigate to a random internal link until we've scraped the desired number of pages.
        for ($pageCtr = 1; $pageCtr < $numPagesToProcess; $pageCtr++) {
            $pageUrl = $internalLinks[array_rand($internalLinks)];

            // Ensure we haven't scraped this page already.
            if (array_key_exists($pageUrl, $scrapedPages)) {
                continue;
            }

            // Scrape and record.
            $scrapedPage = $this->scrapePage($pageUrl);
            $scrapedPages[$scrapedPage->getUrl()] = $scrapedPage;
        }

        return collect($scrapedPages);
    }

    /**
     * Use Guzzle and DOMDocument to scrape a given URL.
     *
     * @param string $url The URL of the page to be scraped
     * @return ScrapedPage
     */
    private function scrapePage(string $url): ScrapedPage
    {
        // Using Guzzle for HTTP requests so that we can get nice status codes, etc.
        $client = new \GuzzleHttp\Client();
        $loadTime = 0;
        $response = $client->get($url, [
            // on_stats callback allows us to check response time.
            'on_stats' => function (\GuzzleHttp\TransferStats $stats) use (&$loadTime) {
                $loadTime = $stats->getTransferTime() * 1000;
            }
        ]);

        /*
        PHP's native DOMDocument can't handle HTML5 tags, even in PHP 8. You can use Goutte with masterminds/html5 for
        HTML5 parsing, but for the purposes of making a web crawler from scratch I am not doing that here.
        */
        libxml_use_internal_errors(true);

        // Load DOM contents.
        $pageXML = new DOMDocument();
        $pageXML->loadHTML((string) $response->getBody());

        libxml_clear_errors();

        $title = '';
        $internalLinks = $externalLinks = $images = [];
        $wordCount = 0;

        // Process all elements in one go to avoid looping unecessarily.
        foreach ($pageXML->getElementsByTagName('*') as $element) {
            switch ($element->nodeName) {
                case 'title':
                    $title = $element->nodeValue;
                    $wordCount += str_word_count($title);
                    break;

                case 'a':

                    // Keep track of interal and external links, indexing to ensure link is unique.
                    $elementBaseUrl = '';
                    $urlResult = parse_url($element->getAttribute('href'));
                    if ($urlResult
                        && array_key_exists('scheme', $urlResult)
                        && array_key_exists('host', $urlResult)
                    ) {
                        $elementBaseUrl = $urlResult['scheme']."://".$urlResult['host'];
                    }

                    if ($elementBaseUrl == $this->baseUrl) {
                        $internalLinks[$element->getAttribute('href')] = $element->getAttribute('href');
                    } elseif (str_starts_with($element->getAttribute('href'), '/')) {
                        $internalLinks[$element->getAttribute('href')] = $this->baseUrl . $element->getAttribute('href');
                    } else {
                        $externalLinks[$element->getAttribute('href')] = $element->getAttribute('href');
                    }

                    $wordCount += str_word_count($element->nodeValue);
                    break;

                case 'img':
                    // Ensure image is unique based on the source.
                    $images[$element->getAttribute('src')] = $element->getAttribute('src');
                    break;

                default:
                    // Determine word count.
                    $wordCount += str_word_count($element->nodeValue);
                    break;
            }
        }

        return new ScrapedPage([
            'statusCode' => $response->getStatusCode(),
            'url' => $url,
            'loadTime' => $loadTime,
            'title' => $title,
            'wordCount' => $wordCount,
            'internalLinks' => $internalLinks,
            'externalLinks' => $externalLinks,
            'images' => $images,
        ]);
    }
}
