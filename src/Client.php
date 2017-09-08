<?php
/**
 * Best Buy SDK
 *
 * High level PHP client for the Best Buy API
 */

namespace BestBuy;

use BestBuy\Exception\AuthorizationException;
use BestBuy\Exception\InvalidArgumentException;
use BestBuy\Exception\ServiceException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * The main client
 */
class Client implements LoggerAwareInterface
{
    /**
     * The endpoint for the most popular viewed products
     */
    const RECOMMENDATIONS_MOSTVIEWED = 'mostViewed';

    /**
     * The endpoint for the most trending viewed products
     */
    const RECOMMENDATIONS_TRENDING = 'trendingViewed';

    /**
     * The endpoint for also viewed products (given an input product)
     */
    const RECOMMENDATIONS_ALSOVIEWED = 'alsoViewed';

    /**
     * The root URL
     */
    const URL_ROOT = 'https://api.bestbuy.com';

    /**
     * The beta URL
     *
     * @TODO - Replace with `self::URL_ROOT . '/beta';` after min PHP version is 5.6
     */
    const URL_BETA = 'https://api.bestbuy.com/beta';

    /**
     * The v1 URL
     *
     * @TODO - Replace with `self::URL_ROOT . '/v1';` after min PHP version is 5.6
     */
    const URL_V1 = 'https://api.bestbuy.com/v1';

    /**
     * The configuration for the class
     *
     * # Available keys:
     *   * `key` - string - Your Best Buy Developer API Key
     *   * `debug` - bool - Whether to log debug information
     *   * `curl_options` - array - An array of options to be passed into {@see curl_setopt_array}
     *   * `associative` - bool - Whether the response should be an associative array (default to {@see \StdClass})
     *
     * @var array
     */
    protected $config = [
        'key' => '',
        'debug' => false,
        'curl_options' => [],
        'associative' => false
    ];

    /**
     * The logger to log to in debug mode
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The package version
     *
     * @var string
     */
    protected $packageVersion = '1.0.4';

    /**
     * Creates new instance
     *
     * @param mixed $options
     *  If array: Merge options into client config
     *  If string: Used as API Key
     *  If null: Look for $_SERVER['BBY_API_KEY']
     */
    public function __construct($options = null)
    {
        // If we didn't get anything, but the key is a server variable, use that
        // Or the `$options` is a string, use that as a key
        // Or the `$options` is an array, merge that into the default options
        if (!$options && isset($_SERVER['BBY_API_KEY'])) {
            $this->config['key'] = $_SERVER['BBY_API_KEY'];
        } elseif (is_string($options)) {
            $this->config['key'] = $options;
        } elseif (is_array($options)) {
            if (isset($options['key'])) {
                $this->config['key'] = $options['key'];
            }

            $this->config = array_merge($this->config, $options);
        }
    }

    /**
     * Adds a curl option to use for future requests
     *
     * @param int $curlOpt The curl option to add for future requests
     * @param mixed $value The value to set for this curl option
     */
    public function addCurlOption($curlOpt, $value)
    {
        $this->config['curl_options'][$curlOpt] = $value;
    }

    /**
     * Retrieve availability of products in stores based on the criteria provided
     *
     * @param int|int[]|string $skus A SKU or SKUs to look for, or a valid product query
     * @param int|int[]|string $stores A Store # or Store #s to look for, or a valid store query
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     * @throws ServiceException
     */
    public function availability($skus, $stores, array $responseConfig = [])
    {
        // if it's a single SKU (either int or only digits), make it to an array (for the next block)
        if (is_int($skus) || ctype_digit($skus)) {
            $skus = [(int)$skus];
        }
        if (is_array($skus)) {
            $skus = 'sku in(' . implode(',', $skus) . ')';
        }

        // if it's a single store (either int or only digits), make it to an array (for the next block)
        if (is_int($stores) || ctype_digit($stores)) {
            $stores = [(int)$stores];
        }
        if (is_array($stores)) {
            $stores = 'storeId in(' . implode(',', $stores) . ')';
        }

        return $this->doRequest(
            self::URL_V1,
            "/products({$skus})+stores({$stores})",
            $responseConfig
        );
    }

    /**
     * Retrieve categories based on the criteria provided
     *
     * @param string $search A category ID or valid query
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     */
    public function categories($search = '', array $responseConfig = [])
    {
        return $this->simpleEndpoint('categories', $search, $responseConfig);
    }

    /**
     * Retrieve open box products based on the criteria provided
     *
     * @param mixed $search int = single SKU; int[] = multiple SKUs; string = query; null = all open box
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     * @throws ServiceException
     */
    public function openBox($search = '', array $responseConfig = [])
    {
        // If the search is an int or string of digits, load the results for that SKU
        // Or the search is an array of SKUs, load the results for those SKUs
        // Or the search is a query (categoryPath.id=*******), load the results in that category
        // Else just get all open box products
        if (is_int($search) || ctype_digit($search)) {
            $path = "/products/{$search}/openBox";
        } elseif (is_array($search)) {
            $skus = implode(',', $search);
            $path = "/products/openBox(sku in({$skus}))";
        } elseif ($search) {
            $path = "/products/openBox({$search})";
        } else {
            $path = '/products/openBox';
        }

        return $this->doRequest(
            self::URL_BETA,
            $path,
            $responseConfig
        );
    }

    /**
     * Retrieve products based on the criteria provided
     *
     * @param string|int $search A product SKU or valid query
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     */
    public function products($search = '', array $responseConfig = [])
    {
        return $this->simpleEndpoint('products', $search, $responseConfig);
    }

    /**
     * Retrieve recommendations based on the criteria provided
     *
     * If using {@see BestBuy\Client::RECOMMENDATIONS_SIMILAR} or {@see BestBuy\Client::RECOMMENDATIONS_ALSOVIEWED}
     * you MUST pass in a SKU.
     *
     * @param string $type One of `\BestBuy\Client::RECOMMENDATIONS_*`
     * @param string|int $categoryIdOrSku A category ID for _TRENDING & _MOSTVIEWED or a SKU for _SIMILAR & _ALSOVIEWED
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     * @throws InvalidArgumentException
     * @throws ServiceException
     */
    public function recommendations($type, $categoryIdOrSku = null, array $responseConfig = [])
    {
        if ($type == self::RECOMMENDATIONS_TRENDING || $type == self::RECOMMENDATIONS_MOSTVIEWED) {
            // Trending & Most viewed work either globally or on a category level, hence the category ID is optional
            $search = $categoryIdOrSku !== null ? "(categoryId={$categoryIdOrSku})" : '';
            $path = "/products/{$type}{$search}";
        } elseif ($type == self::RECOMMENDATIONS_ALSOVIEWED) {
            // Similar & Also viewed work on the SKU level, hence the SKU is required
            if ($categoryIdOrSku === null) {
                throw new InvalidArgumentException(
                    'For `Client::RECOMMENDATIONS_ALSOVIEWED`, a SKU is required'
                );
            }
            $path = "/products/{$categoryIdOrSku}/{$type}";
        } else {
            // The argument passed in isn't a valid recommendation type
            throw new InvalidArgumentException('`$type` must be one of `Client::RECOMMENDATIONS_*`');
        }

        return $this->doRequest(
            self::URL_BETA,
            $path,
            $responseConfig
        );
    }

    /**
     * Retrieve reviews based on the criteria provided
     *
     * @param string|int $search A review ID or valid query
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     */
    public function reviews($search = '', array $responseConfig = [])
    {
        return $this->simpleEndpoint('reviews', $search, $responseConfig);
    }

    /**
     * Sets the associative config value
     *
     * @param bool $associative Whether to return the response as an associative array (defaults to \StdClass)
     */
    public function setAssociative($associative)
    {
        $this->config['associative'] = (bool)$associative;
    }

    /**
     * Sets the curl options to use for future requests
     *
     * @param array $curlOpts The curl options to use for the request
     */
    public function setCurlOptions(array $curlOpts)
    {
        $this->config['curl_options'] = $curlOpts;
    }

    /**
     * Sets whether to run in debug mode
     *
     * @param bool $debug Whether to run in debug mode
     */
    public function setDebug($debug)
    {
        $this->config['debug'] = (bool)$debug;
    }

    /**
     * Sets a logger instance on the object
     *
     * @codeCoverageIgnore
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Retrieve stores based on the criteria provided
     *
     * @param string|int $search A store ID or valid query
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     */
    public function stores($search = '', array $responseConfig = [])
    {
        return $this->simpleEndpoint('stores', $search, $responseConfig);
    }

    /**
     * Retrieves the version of the service from the server & the client version
     *
     * @return array|\StdClass
     */
    public function version()
    {
        $response = [
            'packageVersion' => $this->packageVersion,
            'apiVersion' => $this->doRequest(self::URL_ROOT, '/version.txt', ['__raw' => true])
        ];

        return $this->config['associative'] ? $response : (object)$response;
    }

    /**
     * Retrieve warranties for a product
     *
     * @param int $sku The SKU to find warranties for
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     */
    public function warranties($sku, array $responseConfig = [])
    {
        return $this->doRequest(self::URL_V1, "/products/{$sku}/warranties.json", $responseConfig);
    }

    /**
     * Builds the URL to make the request against
     *
     * @param string $root The Root URL to use ({@see BestBuy\Client::URL_V1} or {@see BestBuy\Client::URL_BETA}
     * @param string $path The path for the endpoint + resources
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     * @throws AuthorizationException
     */
    protected function buildUrl($root, $path, array $responseConfig = [])
    {
        // Verify the client has a key
        if (!$this->config['key']) {
            throw new AuthorizationException(
                'A Best Buy Developer API key is required. Register for one at ' .
                'developer.bestbuy.com, call new `\BestBuy\Client(YOUR_API_KEY)`, or ' .
                'specify a `BBY_API_KEY` system environment variable.'
            );
        }

        $responseConfig['apiKey'] = $this->config['key'];

        // If we're loading just a single resource ({sku}.json), remove the format from the querystring--it'll 400
        if (!preg_match('/\.json$/', $path)) {
            $responseConfig['format'] = 'json';
        }
        $querystring = http_build_query($responseConfig);

        // replace whitespace with url-encoded whitespace
        return preg_replace('/\s+/', '%20', "{$root}{$path}?{$querystring}");
    }

    /**
     * Executes a request & returns the response
     *
     * @param string $root The Root URL to use ({@see BestBuy\Client::URL_V1} or {@see BestBuy\Client::URL_BETA}
     * @param string $path The path for the endpoint + resources
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     * @throws AuthorizationException
     * @throws ServiceException
     */
    protected function doRequest($root, $path, array $responseConfig = [])
    {
        // internal response config to return the raw response from the server
        $raw = array_key_exists('__raw', $responseConfig);
        if ($raw) {
            unset($responseConfig['__raw']);
        }

        // Set up the curl request
        $handle = curl_init($this->buildUrl($root, $path, $responseConfig));
        curl_setopt_array(
            $handle,
            // using `+` to retain indices
            [
                CURLOPT_FAILONERROR => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['User-Agent' => "bestbuy-sdk-php/{$this->packageVersion};php"]
            ] + $this->config['curl_options']
        );

        // Get the response
        $response = curl_exec($handle);

        // Log if needed
        if ($this->config['debug'] && $this->logger) {
            $this->logger->info(var_export(curl_getinfo($handle), true));
        }

        // Check for errors & close the handle
        $curlErrorNumber = curl_errno($handle);
        $curlErrorText = curl_error($handle);
        curl_close($handle);

        // If we have an error code, log if needed & bail out
        if ($curlErrorNumber) {
            if ($this->logger) {
                $this->logger->error($curlErrorText);
            }
            throw new ServiceException('An error occurred when communicating with the service');
        }

        // Return the response in the configured format
        if ($raw) {
            return trim($response);
        } else {
            return json_decode($response, $this->config['associative']);
        }
    }

    /**
     * Handles standard endpoints (products, stores, categories, reviews)
     *
     * @param string $endpoint The base endpoint to retrieve data from
     * @param string|int $search The identifier of an object or a valid query
     * @param array $responseConfig The additional filters to apply to the result set (pagination, view, sort, etc.)
     * @return array|\StdClass
     * @throws ServiceException
     */
    protected function simpleEndpoint($endpoint, $search, array $responseConfig = [])
    {
        // If it's an integer (or a string that's only digits), or a category id, load the resource directly
        // Or we have a valid query, load the result of that query
        // Else load all resources
        if (is_int($search) || ctype_digit($search) || preg_match('/^(cat|pcmcat|abcat)\d+$/', $search)) {
            $path = "/{$endpoint}/{$search}.json";
        } elseif ($search) {
            $path = "/{$endpoint}({$search})";
        } else {
            $path = "/{$endpoint}";
        }

        return $this->doRequest(
            self::URL_V1,
            $path,
            $responseConfig
        );
    }
}
