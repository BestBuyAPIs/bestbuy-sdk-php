# Best Buy SDK for PHP [![Build Status](https://secure.travis-ci.org/BestBuyAPIs/bestbuy-sdk-php.svg?branch=master)](http://travis-ci.org/BestBuyAPIs/bestbuy-sdk-php)

[![License](http://img.shields.io/packagist/l/bestbuy/bestbuy.svg)](https://github.com/BestBuyAPIs/bestbuy-sdk-php/blob/master/LICENSE)
[![Latest Stable Version](http://img.shields.io/github/release/BestBuyAPIs/bestbuy-sdk-php.svg)](https://packagist.org/packages/bestbuy/bestbuy)
[![Coverage Status](http://img.shields.io/coveralls/BestBuyAPIs/bestbuy-sdk-php.svg)](https://coveralls.io/r/BestBuyAPIs/bestbuy-sdk-php?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/bestbuy/bestbuy.svg)](https://packagist.org/packages/bestbuy/bestbuy)

This is a high-level PHP client for the [Best Buy developer API](https://developer.bestbuy.com/).

## Getting Started
 1. Sign-up for a developer API Key at https://developer.bestbuy.com/
 2. Install the package
    * Using the command line<br>
      `composer require bestbuy/bestbuy`
    * Using `composer.json`<br>
      Add `"bestbuy/bestbuy": "^1.0"` inside of the *require* part of your `composer.json` file:
      
      ```json
      "require": {
        "bestbuy/bestbuy": "^1.0"
      }
      ```
 3. Use the package. There are several ways to provide the key to the `Client`:
    * Set an environment variable of `BBY_API_KEY` to your key and invoke the method<br>
      `$bby = new \BestBuy\Client();`
    * Send the key in as a string when invoking the method<br>
      `$bby = new \BestBuy\Client('YOURKEY');`
    * Send the key in as part of an object when invoking the method<br>
      `$bby = new \BestBuy\Client(['key' => 'YOURKEY']);`
      
## Documentation

 * [Store Availability](#Store-Availability)
 * [Product Categories](#Product-Categories)
 * [Open Box Products](#Open-Box-Products)
 * [Product Information](#Product-Information)
 * [Product Recommendations](#Product-Recommendations)
 * [Product Reviews](#Product-Reviews)
 * [Stores](#Stores)
 
### Store Availability
#### `$bby->availability(int|int[]|string $skus, int|int[]|string $stores, [array $responseConfig = []]);`

  1. A single SKU/Store #<br>
    `$bby->availability(6354884, 611);`
  2. An array of SKUs/Store #s<br>
    `$bby->availability([6354884, 69944141], [611, 281]);`
  3. A valid query for SKUs/Stores<br>
    `$bby->availability('name=Star*', 'area(55347, 25)');`

### Product Categories 
#### `$bby->categories(string $search = '', [array $responseConfig = []]);`

  1. All categories<br>
    `$bby->categories();`
  2. A single category<br>
    `$bby->categories('cat00000');`
  3. A query for categories<br>
    `$bby->categories('name=Home*');`
    
### Open Box Products
#### `$bby->openBox(int|int[]|string $search = '', [array $responseConfig = []]);`

  1. All open box products<br>
    `$bby->openBox();`
  2. A single product<br>
    `$bby->openBox(6354884);`
  3. An array of products<br>
    `$bby->openBox([6354884, 69944141]);`
  4. A query<br>
    `$bby->openBox('category.id=cat00000');`
    
### Product Information 
#### `$bby->products(int|string $search = '', [array $responseConfig = []]);`

  1. All products<br>
    `$bby->products();`
  2. A single product<br>
    `$bby->products(6354884);`
  3. A query for products<br>
    `$bby->products('name=Star*');`
    
### Product Recommendations 
#### `$bby->recommendations(string $type, int|string $categoryIdOrSku = null, [array $responseConfig = []]);`

  1. Trending or Most Viewed products<br>
    `$bby->recommendations(\BestBuy\Client::RECOMMENDATIONS_TRENDING);`<br>
    `$bby->recommendations(\BestBuy\Client::RECOMMENDATIONS_TRENDING, 'cat00000');`<br>
  2. Similar or Also Viewed products<br>
    `$bby->recommendations(\BestBuy\Client::RECOMMENDATIONS_SIMILAR, 6354884);`
    
### Product Reviews 
#### `$bby->reviews(int|string $search = '', [array $responseConfig = []]);`

  1. All reviews<br>
    `$bby->reviews();`
  2. A single review<br>
    `$bby->reviews(69944141);`
  3. A query for reviews<br>
    `$bby->reviews('comment=purchase*');`
    
### Stores 
#### `$bby->stores(int|string $search = '', [array $responseConfig = []]);`

  1. All stores<br>
    `$bby->stores();`
  2. A single store<br>
    `$bby->stores(611);`
  3. A query for stores<br>
    `$bby->stores('name=eden*');`