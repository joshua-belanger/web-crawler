<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebCrawlerRequest;
use App\Http\Resources\WebCrawlerResultsResource;
use App\Models\ScrapedPage;
use Illuminate\Http\Response;
use DOMDocument;

class WebCrawlerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return WebCrawlerResultsResource
     */
    public function index(WebCrawlerRequest $request): WebCrawlerResultsResource
    {
        $pageData = [];

        $requestData = $request->validated();

        // TODO guzzle to get a bunch of pages from a domain
        // foreach () while $requestData['numPages'] < 4
       
        // TODO move this into a service or something

        $pageData[] = $this->scrapePage($requestData['url']);

        return new WebCrawlerResultsResource($pageData);
    }

    /**
     * Scrape a given URL.
     * 
     * @param string $url The URL of the page to be scraped
     * @param string $statusCode The response code of the page that was crawled
     * @return ScrapedPage
     */
    private function scrapePage(string $url, $statusCode = Response::HTTP_OK): ScrapedPage
    {
        $pageXML = new DOMDocument();

        // DOMDocument can't handle HTML 5 tags even in PHP 8.
        libxml_use_internal_errors(true);

        // Load contents into DOM.
        $pageXML->loadHTMLFile($url);

        libxml_clear_errors();

        $title = '';
        $internalLinks = $externalLinks = $images = [];
        $wordCount = 0;

        foreach($pageXML->getElementsByTagName('*') as $element) {

            // TODO factory pattern or similar if I have time.
            switch ($element->nodeName) {

                case 'title':
                    $title = $element->nodeValue;
                    $wordCount += str_word_count($title);
                    break;

                case 'a':
                    // Keep track of interal and external links, indexing to ensure link is unique.
                    if (str_contains($element->getAttribute('href'), 'agencyanalytics.com')) { 
                        $internalLinks[$element->getAttribute('href')] = $element->getAttribute('href');
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

        $scraped = new ScrapedPage([
            'statusCode' => $statusCode,
            'url' => $url,
            'title' => $title,
            'wordCount' => $wordCount,
            'internalLinks' => $internalLinks,
            'externalLinks' => $externalLinks,
            'images' => $images,
        ]);

        dd($scraped);
    }
}
