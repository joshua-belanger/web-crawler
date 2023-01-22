<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebCrawlerRequest;
use App\Http\Resources\WebCrawlerResultsResource;
use App\Models\ScrapedPage;
use Illuminate\Http\Response;
use DOMDocument;
use Illuminate\Contracts\View\View;

class WebCrawlerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(WebCrawlerRequest $request): View
    {
        $requestData = $request->validated();
        $scrapedPages = [];

        // Use the requested URL as an entry point to gather additional internal links.
        $urlResult = parse_url($requestData['url']);
        $baseUrl = $urlResult['scheme']."://".$urlResult['host'];
        $scrapedEntryPage = $this->scrapePage($baseUrl, $requestData['url']);

        // Record page as scraped.
        $scrapedPages[$scrapedEntryPage->getUrl()] = $scrapedEntryPage;
        // We will use the internal links to crawl more of the site.
        $internalLinks = $scrapedEntryPage->getInternalLinks();

        // The # of internal links may not be enough to accomodate the number of pages requested.
        $numPagesToProcess = count($internalLinks) < $requestData['numPages'] 
            ? count($internalLinks) : $requestData['numPages'];

        // Navigate to a random internal link until we've scraped the desired number of pages.
        for ($pageCtr = 1; $pageCtr < $numPagesToProcess; $pageCtr++) {
            $pageUrl = $internalLinks[array_rand($internalLinks)];

            // Ensure we haven't scraped this page already.
            if (array_key_exists($pageUrl, $scrapedPages)) {
                continue;
            }

            // Scrape and record.
            $scrapedPage = $this->scrapePage($baseUrl, $pageUrl);
            $scrapedPages[$scrapedPage->getUrl()] = $scrapedPage;
        }

        // We've now scraped the desired number of pages, roll up as a resource.
        $rollUp = new WebCrawlerResultsResource($scrapedPages);

        return view('webcrawlerresults', [
            'totals' => $rollUp->toArray($scrapedPages),
            'scrapedPages' => $scrapedPages,
        ]);
    }

    /**
     * Scrape a given URL.
     * 
     * @param string $baseUrl The base URL of the internal domain, used to build out internal links
     * @param string $url The URL of the page to be scraped
     * @param string $statusCode The response code of the page that was crawled
     * @return ScrapedPage
     */
    private function scrapePage(string $baseUrl, string $url, $statusCode = Response::HTTP_OK): ScrapedPage
    {
        $pageXML = new DOMDocument();

        /*  
        PHP's native DOMDocument can't handle HTML5 tags, even in PHP 8. You can use Goutte with masterminds/html5 for 
        HTML5 parsing, but for the purposes of making a web crawler from scratch I am not doing that here.
        */
        libxml_use_internal_errors(true);

        // Load DOM contents, keepting track of how long it takes.
        $startTime = floor(microtime(true) * 1000);
        $pageXML->loadHTMLFile($url);
        $endTime = floor(microtime(true) * 1000);

        libxml_clear_errors();

        $title = '';
        $internalLinks = $externalLinks = $images = [];
        $wordCount = 0;

        // Process all elements in one go to avoid looping unecessarily.
        foreach($pageXML->getElementsByTagName('*') as $element) {
            switch ($element->nodeName) {

                case 'title':
                    $title = $element->nodeValue;
                    $wordCount += str_word_count($title);
                    break;

                case 'a':

                    $elementBaseUrl = '';
                    $urlResult = parse_url($element->getAttribute('href'));
                    if ($urlResult 
                        && array_key_exists('scheme', $urlResult) 
                        && array_key_exists('host', $urlResult)
                    ) {
                        $elementBaseUrl = $urlResult['scheme']."://".$urlResult['host'];
                    }

                    // Keep track of interal and external links, indexing to ensure link is unique.
                    if ($elementBaseUrl == $baseUrl || str_starts_with($element->getAttribute('href'), '/')) { 
                        $internalLinks[$element->getAttribute('href')] = $baseUrl . $element->getAttribute('href');
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
            'statusCode' => $statusCode,
            'url' => $url,
            'loadTime' => $endTime - $startTime,
            'title' => $title,
            'wordCount' => $wordCount,
            'internalLinks' => $internalLinks,
            'externalLinks' => $externalLinks,
            'images' => $images,
        ]);
    }
}
