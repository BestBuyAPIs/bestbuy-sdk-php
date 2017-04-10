<?php
/**
 * Best Buy SDK
 *
 * High level PHP client for the Best Buy API
 */

namespace BestBuy\Tests\Integration;

use BestBuy\Client;
use Psr\Log\NullLogger;

/**
 * Test cases for interacting with the live service
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Sets up our client with logger before each test
     */
    public function setUp()
    {
        $this->apiKey = getenv('BBY_API_KEY');
        $this->client = new Client(['debug' => true, 'key' => $this->apiKey]);
        $this->client->setLogger(new NullLogger());
    }

    /**
     * Verifies store availability endpoint
     */
    public function testAvailability()
    {
        $availability = $this->client->availability(6354884, 611);

        $this->assertEquals(
            "/v1/products(sku in(6354884))+stores(storeId in(611))?format=json&apiKey={$this->apiKey}",
            $availability->canonicalUrl
        );
        $this->assertEquals(0, count($availability->products));

        $this->throttle();
    }

    /**
     * Verifies categories endpoint
     */
    public function testCategories()
    {
        $categories = $this->client->categories('cat00000');

        $this->assertEquals(
            'Best Buy',
            $categories->name
        );

        $this->throttle();
    }

    /**
     * Verifies open box endpoint
     */
    public function testOpenBox()
    {
        $openBox = $this->client->openBox(6354884);

        $this->assertEquals(
            "https://api.bestbuy.com/beta/products/6354884/openBox?apiKey={$this->apiKey}&format=json",
            $openBox->metadata->context->canonicalUrl
        );

        $this->throttle();
    }

    /**
     * Verifies products endpoint
     */
    public function testProducts()
    {
        $products = $this->client->products('sku=6354884&active=*');

        $this->assertEquals(
            "/v1/products(sku=6354884&active=*)?format=json&apiKey={$this->apiKey}",
            $products->canonicalUrl
        );
        $this->assertEquals(
            'Lexmark - Color Jetprinter',
            $products->products[0]->name
        );

        $this->throttle();
    }

    /**
     * Verifies decoding to an associate array works
     */
    public function testProductsAssociative()
    {
        $client = new Client(['associative' => true, 'debug' => true, 'key' => $this->apiKey]);
        $products = $client->products('sku=6354884&active=*');

        $this->assertEquals(
            "/v1/products(sku=6354884&active=*)?format=json&apiKey={$this->apiKey}",
            $products['canonicalUrl']
        );
        $this->assertEquals(
            'Lexmark - Color Jetprinter',
            $products['products'][0]['name']
        );

        $this->throttle();
    }

    /**
     * Verifies the recommendations endpoint
     */
    public function testRecommendations()
    {
        $recommendations = $this->client->recommendations(Client::RECOMMENDATIONS_TRENDING);

        $this->assertEquals(
            "https://api.bestbuy.com/beta/products/trendingViewed?apiKey={$this->apiKey}&format=json",
            $recommendations->metadata->context->canonicalUrl
        );

        $this->throttle();
    }

    /**
     * Verifies the reviews endpoint
     */
    public function testReviews()
    {
        $reviews = $this->client->reviews();

        $this->assertEquals("/v1/reviews?format=json&apiKey={$this->apiKey}", $reviews->canonicalUrl);
        $this->assertGreaterThan(10000, $reviews->total);

        $this->throttle();
    }

    /**
     * Verifies the stores endpoint
     */
    public function testStores()
    {
        $stores = $this->client->stores(611);

        $this->assertEquals(611, $stores->storeId);

        $this->throttle();
    }

    /**
     * Verifies http errors are flagged as such by the curl handle
     *
     * @expectedException \BestBuy\Exception\ServiceException
     */
    public function testError()
    {
        $this->client->products('fdkasj');
    }

    /**
     * Throttles calls so we don't get nastygrams. This makes it so we're < 4 calls / second
     */
    protected function throttle()
    {
        usleep(250);
    }
}