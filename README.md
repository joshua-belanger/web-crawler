# Web Crawler

A simple PHP web crawler that uses <a href="https://docs.guzzlephp.org/">Guzzle</a> and PHP's native <a href="https://www.php.net/manual/en/class.domdocument.php">DOMDocument</a> to scrape pages. 

## How to run the scraper

Clone the project and run `php artisan serve` to run the project locally. Hit the index (`/`) or `/scrape` to run the scraper e.g.: `/scrape?numPages=4&url=https://google.com`. Default rate limiting is set to 5 requests every 10 minutes, modify the web route to adjust to your preference.
