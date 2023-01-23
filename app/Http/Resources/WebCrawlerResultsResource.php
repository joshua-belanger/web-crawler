<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ScrapedPage;

class WebCrawlerResultsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $loadTimeTotal = $wordCountTotal = $titleLengthTotal = 0;

        $rollUp = [
            'numPages' => 0,
            'numImages' => 0,
            'numInternalLinks' => 0,
            'numExternalLinks' => 0,
        ];

        // Aggregate totals.
        /** @var ScrapedPage $scrapedPage */
        foreach ($this->resource as $scrapedPage) {
            $rollUp['numPages']++;
            $rollUp['numImages']+= count($scrapedPage->getImages());
            $rollUp['numInternalLinks']+= count($scrapedPage->getInternalLinks());
            $rollUp['numExternalLinks']+= count($scrapedPage->getExternalLinks());
            $loadTimeTotal+= $scrapedPage->getLoadTime();
            $wordCountTotal+= $scrapedPage->getWordCount();
            $titleLengthTotal+= strlen($scrapedPage->getTitle());
        }

        // Calculate averages.
        $rollUp['avgLoadTime'] = round($loadTimeTotal / $rollUp['numPages']);
        $rollUp['avgWordCount'] = round($wordCountTotal / $rollUp['numPages']);
        $rollUp['avgTitleLength'] = round($titleLengthTotal / $rollUp['numPages']);

        return $rollUp;
    }
}
