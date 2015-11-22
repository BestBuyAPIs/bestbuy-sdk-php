<?php
/**
 * Best Buy SDK
 *
 * High level PHP client for the Best Buy API
 */

namespace BestBuy\Tests\Unit;

use BestBuy\Client;

/**
 * Unit tests for the client
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The apiKey (grabbed from $_SERVER for easier use)
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The mocked client
     *
     * @var Client
     */
    protected $client;

    /**
     * Sets up our client and key before each test
     */
    public function setUp()
    {
        $this->apiKey = $_SERVER['BBY_API_KEY'];
        $this->client = $this->getMock(Client::class, ['doRequest']);
        $this->client->expects($this->any())
            ->method('doRequest')
            ->willReturnCallback([$this, 'getGeneratedUrl']);
    }

    /**
     * Make sure the key is gathered in each way to create the client
     */
    public function testConstruct()
    {
        // test no key, string key, array + key
        $clients = [new Client(), new Client($this->apiKey), new Client(['key' => $this->apiKey])];
        foreach ($clients as $client) {
            $reflection = new \ReflectionClass($client);
            $prop = $reflection->getProperty('config');
            $prop->setAccessible(true);
            $config = $prop->getValue($client);

            $this->assertEquals($this->apiKey, $config['key']);
        }
    }

    /**
     * Make sure the URLs are generated successfully
     */
    public function testBuildUrl()
    {
        $this->assertEquals(
            "https://api.bestbuy.com/v1/products/123.json?view=all&apiKey={$this->apiKey}",
            $this->getGeneratedUrl(Client::URL_V1, '/products/123.json', ['view' => 'all'])
        );
        $this->assertEquals(
            "https://api.bestbuy.com/beta/openBox(sku%20in(123,456))?view=all&apiKey={$this->apiKey}&format=json",
            $this->getGeneratedUrl(Client::URL_BETA, '/openBox(sku in(123,456))', ['view' => 'all'])
        );
    }

    /**
     * Tests generated URLs for all endpoints
     */
    public function testGeneratedUrls()
    {
        // we're not using @dataProvider so we can access class props
        $host = 'https://api.bestbuy.com';
        $callsAndUrls = [
            [
                "{$host}/v1/products(sku%20in(4312001))+stores(storeId%20in(611))?apiKey={$this->apiKey}&format=json",
                $this->client->availability(4312001, 611)
            ], [
                "{$host}/v1/products(sku%20in(4312001,6120183))+stores(storeId%20in(611,482))?apiKey={$this->apiKey}&format=json",
                $this->client->availability([4312001, 6120183], [611, 482])
            ], [
                "{$host}/v1/products(name=Star*)+stores(area(55347,%2025))?apiKey={$this->apiKey}&format=json",
                $this->client->availability('name=Star*', 'area(55347, 25)')
            ], [
                "{$host}/v1/products(fdafsd)+stores(storeId%20in(611))?apiKey={$this->apiKey}&format=json",
                $this->client->availability('fdafsd', 611)
            ], [
                "{$host}/v1/categories?apiKey={$this->apiKey}&format=json",
                $this->client->categories()
            ], [
                "{$host}/v1/categories/cat00000.json?apiKey={$this->apiKey}",
                $this->client->categories('cat00000')
            ], [
                "{$host}/v1/categories(name=Home*)?apiKey={$this->apiKey}&format=json",
                $this->client->categories('name=Home*')
            ], [
                "{$host}/beta/products/openBox?apiKey={$this->apiKey}&format=json",
                $this->client->openBox()
            ], [
                "{$host}/beta/products/2206525/openBox?apiKey={$this->apiKey}&format=json",
                $this->client->openBox(2206525)
            ], [
                "{$host}/beta/products/openBox(sku%20in(8610161,2206525))?apiKey={$this->apiKey}&format=json",
                $this->client->openBox([8610161, 2206525])
            ], [
                "{$host}/beta/products/openBox(categoryId=abcat0400000)?apiKey={$this->apiKey}&format=json",
                $this->client->openBox('categoryId=abcat0400000')
            ], [
                "{$host}/v1/products?apiKey={$this->apiKey}&format=json",
                $this->client->products()
            ], [
                "{$host}/v1/products/4312001.json?apiKey={$this->apiKey}",
                $this->client->products('4312001')
            ], [
                "{$host}/v1/products(name=Star*)?apiKey={$this->apiKey}&format=json",
                $this->client->products('name=Star*')
            ], [
                "{$host}/beta/products/trendingViewed?apiKey={$this->apiKey}&format=json",
                $this->client->recommendations(Client::RECOMMENDATIONS_TRENDING)
            ], [
                "{$host}/beta/products/trendingViewed(categoryId=abcat0400000)?apiKey={$this->apiKey}&format=json",
                $this->client->recommendations(Client::RECOMMENDATIONS_TRENDING, 'abcat0400000')
            ], [
                "{$host}/beta/products/6354884/similar?apiKey={$this->apiKey}&format=json",
                $this->client->recommendations(Client::RECOMMENDATIONS_SIMILAR, 6354884)
            ], [
                "{$host}/v1/reviews?apiKey={$this->apiKey}&format=json",
                $this->client->reviews()
            ], [
                "{$host}/v1/reviews/69944141.json?apiKey={$this->apiKey}",
                $this->client->reviews('69944141')
            ], [
                "{$host}/v1/reviews(comment=purchase*)?apiKey={$this->apiKey}&format=json",
                $this->client->reviews('comment=purchase*')
            ], [
                "{$host}/v1/stores?apiKey={$this->apiKey}&format=json",
                $this->client->stores()
            ], [
                "{$host}/v1/stores/611.json?apiKey={$this->apiKey}",
                $this->client->stores('611')
            ], [
                "{$host}/v1/stores(name=eden%20prairie)?apiKey={$this->apiKey}&format=json",
                $this->client->stores('name=eden prairie')
            ],
        ];
        foreach ($callsAndUrls as $callAndUrl) {
            $this->assertEquals($callAndUrl[0], $callAndUrl[1]);
        }
    }

    /**
     * Tests checking the recommendation mode
     *
     * @expectedException \BestBuy\Exception\InvalidArgumentException
     */
    public function testBadRecommendationMode()
    {
        $this->client->recommendations('bad');
    }

    /**
     * Tests checking for SKU for the similar recommendations mode
     *
     * @expectedException \BestBuy\Exception\InvalidArgumentException
     */
    public function testNoSkuForSimilar()
    {
        $this->client->recommendations(Client::RECOMMENDATIONS_SIMILAR);
    }

    /**
     * Tests checking for a key before making a call
     *
     * @expectedException \BestBuy\Exception\AuthorizationException
     */
    public function testNoKey()
    {
        unset($_SERVER['BBY_API_KEY']);

        $client = new Client();

        $client->products();
    }

    /**
     * Gets the generated URL from protected method (used in the mock client)
     *
     * @return string
     */
    public function getGeneratedUrl()
    {
        $reflection = new \ReflectionClass(get_class($this->client));
        $buildURLMethod = $reflection->getMethod('buildUrl');
        $buildURLMethod->setAccessible(true);

        return $buildURLMethod->invokeArgs($this->client, func_get_args());
    }
}