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
     * @return array $rollUp
     */
    public function toArray($request): array
    {
        $loadTimeTotal = $wordCountTotal = $titleLengthTotal = 0;

        $rollUp = [
            'numPages' => 0,
            'numImages' => 0,
            'numInternalLinks' => 0,
            'numExternalLinks' => 0,
            'avgLoadTime' => 0,
            'avgWordCount' => 0,
            'avgTitleLength' => 0,
        ];

        if (count($this->resource) < 1) {
            return $rollUp;
        }

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
