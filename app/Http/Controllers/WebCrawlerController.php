<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebCrawlerRequest;
use App\Http\Resources\WebCrawlerResultsResource;
use App\Services\WebCrawlerService;
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

        $webCrawler = new WebCrawlerService($requestData['url'], $requestData['numPages']);
        $scrapedPages = $webCrawler->scrapeDomain();

        $totals = new WebCrawlerResultsResource($scrapedPages ? $scrapedPages : collect());

        return view('webcrawlerresults', [
            'totals' => $totals->toArray($scrapedPages),
            'scrapedPages' => $scrapedPages,
        ]);
    }
}
