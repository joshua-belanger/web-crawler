<?php

namespace App\Models;


class ScrapedPage 
{
    protected $statusCode, $url, $title;
    private $internalLinks, $externalLinks, $images;
    private $loadTime, $wordCount;

    public function __construct(array $props = array()){
        foreach ($props as $key => $value){
          $this->{$key} = $value;
        }
    }

    // public function __set($prop, $value){
    //     return $this->_data[$prop] = $value;
    // }
  
    // public function __get($prop){
    //     return array_key_exists($prop, $this->_data) ? $this->_data[$prop]: null;
    // }

    /**
     * @return string
     */
    public function getStatusCode() 
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getUrl(): string 
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getTitle(): string 
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getInternalLinks(): array 
    {
        return $this->internalLinks;
    }

    /**
     * @return array
     */
    public function getExternalLinks(): array 
    {
        return $this->externalLinks;
    }

    /**
     * @return int
     */
    public function getLoadTime(): int 
    {
        return $this->loadTime;
    }

    /**
     * @return int
     */
    public function getWordCount(): int 
    {
        return $this->wordCount;
    }
}
