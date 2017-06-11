<?php
namespace Apatis\Transporter;

use Apatis\Exceptions\InvalidArgumentException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class AbstractTransport
 * @package Apatis\Transporter
 *
 * @method static AbstractTransport connect(string $url, array $config = [])
 * @method static AbstractTransport copy(string $url, array $config = [])
 * @method static AbstractTransport delete(string $url, array $config = [])
 * @method static AbstractTransport get(string $url, array $config = [])
 * @method static AbstractTransport head(string $url, array $config = [])
 * @method static AbstractTransport link(string $url, array $config = [])
 * @method static AbstractTransport lock(string $url, array $config = [])
 * @method static AbstractTransport options(string $url, array $config = [])
 * @method static AbstractTransport post(string $url, array $config = [])
 * @method static AbstractTransport put(string $url, array $config = [])
 * @method static AbstractTransport purge(string $url, array $config = [])
 * @method static AbstractTransport patch(string $url, array $config = [])
 * @method static AbstractTransport propFind(string $url, array $config = [])
 * @method static AbstractTransport trace(string $url, array $config = [])
 * @method static AbstractTransport unlink(string $url, array $config = [])
 * @method static AbstractTransport unlock(string $url, array $config = [])
 * @method static AbstractTransport view(string $url, array $config = [])
 */
abstract class AbstractTransport implements TransportInterface
{
    /**
     * Current Method
     *
     * @var string
     */
    protected $method = TransportInterface::DEFAULT_METHOD;

    /**
     * Use as Common Browsers
     *
     * @var array
     */
    protected $configs_default = [
        'headers' => [
            'User-Agent' => [TransportUtil::DEFAULT_USER_AGENT],
        ],
        'timeout'         => 10,
        'allow_redirects' => true,
    ];

    /**
     * @var array|CookieJar
     */
    protected $configs = [];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var TransportResponse
     */
    protected $last_response;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $currentParamType = self::PARAM_FORM;

    /**
     * @var bool
     */
    protected $inProcessingLoop = false;

    /**
     * AbstractTransport constructor.
     *
     * @param string $uri   the url to get
     * @param array $config array configuration
     */
    public function __construct($uri, array $config = [])
    {
        $this->inProcessingLoop = false;
        if (is_string($uri)) {
            $config['base_uri'] = $uri;
        }

        /**
         * Just Manipulate
         */
        $this->configs = array_merge($this->configs_default, $config);
        // roll headers to default
        if (!is_array($this->configs['headers'])) {
            $this->configs['headers'] = $this->configs_default['headers'];
        }
        foreach ($this->configs['headers'] as $key => $value) {
            $key = $this->normalizeHeaderName($key);
            if (!$key) {
                unset($this->configs['headers'][$key]);
            }
            if (!is_array($value)) {
                $this->configs['headers'][$key] = [$value];
            }
        }

        $this->request = new Request($this->method, $uri);
        $this->client  = new Client($this->configs);
    }

    /**
     * Normalize Real Header Name
     *
     * @param string $keyName Header Name key, The key will be convert into
     *                        First Character after `-`(dash) into uppercase
     *                        And space will be replace as `dash`
     * @return null|string
     */
    protected function normalizeHeaderName($keyName)
    {
        if (!is_string($keyName)) {
            return null;
        }

        return ucwords(trim(strtolower($keyName)), '-') ?: null;
    }
    /**
     * Replace Headers Value
     *
     * @param array $headers collection headers array
     * @return static
     */
    public function replaceHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
        return $this;
    }

    /**
     * Replace Headers Value
     *
     * @param array $headers collection headers array
     * @return static
     */
    public function removeHeaders(array $headers)
    {
        $this->inProcessingLoop = true;
        foreach ($headers as $value) {
            if (is_string($value) || is_numeric($value)) {
                unset($this->configs['headers'][$value]);
            }
        }

        $this->inProcessingLoop = false;
        return $this->buildConfigClient();
    }

    /**
     * Remove Existing Header
     *
     * @param string $name
     * @return static
     */
    public function removeHeader($name)
    {
        return $this->removeHeaders([$name]);
    }

    /**
     * Set Header
     *
     * @param string $keyName Header Name key, The key will be convert into
     *                        First Character after `-`(dash) into uppercase
     *                        And space will be replace as `dash`
     * @param string $value
     * @return static
     */
    public function setHeader($keyName, $value)
    {
        $keyName = $this->normalizeHeaderName($keyName);
        if (!$keyName) {
            return $this;
        }

        if (!isset($this->configs['headers'])) {
            $this->configs['headers'] = [];
        }

        $this->configs['headers'][$keyName] = [$value];
        if (!$this->inProcessingLoop) {
            return $this
                ->buildConfigClient()
                ->withRequest($this->request->withHeader($keyName, $value));
        }

        return $this
            ->withRequest($this->request->withHeader($keyName, $value));
    }

    /**
     * Method Allowed
     *
     * @param string $method
     * @return bool|string the method
     */
    public static function allowedMethod($method)
    {
        if (is_string($method) && ($method = strtoupper(trim($method))) != '') {
            return defined(static::class . '::METHOD_'. $method) ? $method : false;
        }

        return false;
    }

    /**
     * Build Config Client
     *
     * @return static
     */
    protected function buildConfigClient()
    {
        $this->client = new Client($this->configs);
        return $this;
    }

    /**
     * Set Config
     *
     * @param mixed $name
     * @param mixed $value
     * @return static
     */
    public function setConfig($name, $value)
    {
        if ($name == self::PARAM_FORM || $name == self::PARAM_MULTIPART) {
            return $this;
        }

        if ($name == 'headers' || $name == self::PARAM_FORM || $name == self::PARAM_MULTIPART) {
            if (is_array($value)) {
                $obj = $name == 'headers'
                    ? $this->withHeaders($value)
                    : $this->withParams($value, $name);
                $this->configs = $obj->configs;
                $this->client  = $obj->client;
                $this->request = $obj->request;
            }

            return $this;
        }

        $this->configs[$name] = $value;
        return $this->buildConfigClient();
    }

    /**
     * Get Config
     *
     * @param null|string $name
     * @return array|mixed|null
     */
    public function getConfig($name = null)
    {
        return is_null($name)
            ? $this->configs
            : (isset($this->configs[$name]) ? $this->configs[$name] : null);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return \Exception|ResponseInterface
     */
    public function getResponse()
    {
        return ! $this->last_response instanceof TransportResponse
            ? $this->send()->getResponse()
            : $this->last_response->getResponse();
    }

    /**
     * @return TransportResponse
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * @param Client $client
     * @return static
     */
    public function withClient(Client $client)
    {
        $object = clone $this;
        $object->client = $client;
        $this->configs  = $client->getConfig();
        return $object;
    }

    /**
     * With Set Headers
     *
     * @param array $headers
     * @return static
     */
    public function withHeaders(array $headers)
    {
        $object = clone $this;
        $object->configs['headers'] = [];
        $object->inProcessingLoop = true;
        $object->replaceHeaders($headers);
        $object->inProcessingLoop = true;
        return $object->buildConfigClient();
    }

    /**
     * With Added Current Headers
     *
     * @param array $headers
     * @return static
     */
    public function withAddedHeaders(array $headers)
    {
        $object = clone $this;
        $object->inProcessingLoop = false;
        $object->configs['headers'] = [];
        foreach ($headers as $keyName => $value) {
            $keyName = $this->normalizeHeaderName($keyName);
            if (!$keyName) {
                continue;
            }

            if (!isset($this->configs['headers'])) {
                $object->configs['headers'] = [];
            }

            $object->configs['headers'][$keyName] = !is_array($value) ? [$value] : $value;
            $object->request = $object->request->withAddedHeader($keyName, $value);
        }

        return $object->buildConfigClient();
    }

    /**
     * With Added Current Headers
     *
     * @param mixed $header
     * @return static
     */
    public function withoutHeader($header)
    {
        $object = clone $this;
        $headerName = $object->normalizeHeaderName($header);
        if (isset($this->configs['headers'][$headerName])) {
            unset($this->configs['headers'][$headerName]);
        }

        $object->request = $object->request->withoutHeader($headerName);
        return $object->buildConfigClient();
    }

    /**
     * @param CookieJarInterface $cookieJar
     * @return static
     */
    public function withCookieJar(CookieJarInterface $cookieJar)
    {
        $object = clone $this;
        $object->configs['cookies'] = $cookieJar;
        return $object->buildConfigClient();
    }

    /**
     * @param array $cookie
     * @param string $domain
     * @return static
     */
    public function withCookieArray(array $cookie, $domain = null)
    {
        $object = clone $this;
        $object->configs['cookies'] = $domain
            ? CookieJar::fromArray($cookie, $domain)
            : new CookieJar(false, $cookie);
        return $object->buildConfigClient();
    }

    /**
     * Without Send Cookie
     *
     * @param string|array $cookieName
     * @return static
     */
    public function withoutCookie($cookieName = null)
    {
        $object = clone $this;
        if (! $this->configs['cookies'] instanceof CookieJarInterface) {
            return $object;
        }

        if (!$cookieName) {
            unset($object->configs['cookies']);
        } else {
            if (!is_array($cookieName)) {
                $cookieName = [$cookieName];
            }
            $cookies = $this->configs['cookies']->toArray();
            foreach ($cookieName as $cookie) {
                if (!is_string($cookie) || !$cookie) {
                    continue;
                }
                unset($cookies[$cookie]);
            }

            $this->configs['cookies'] = new CookieJar($cookies);
        }

        return $object->buildConfigClient();
    }

    /**
     * With Params
     *
     * @param array $params array parameter to set
     * @param string $type default use TransportInterface::PARAM_FORM
     * @return static
     * @throws InvalidArgumentException
     */
    public function withParams(array $params = [], $type = null)
    {
        $object = clone $this;
        if (!$type && isset($this->configs[$this->currentParamType])) {
            $type = $this->currentParamType;
        }

        if (! $type && (
            isset($this->configs[self::PARAM_FORM]) || isset($this->configs[self::PARAM_MULTIPART]))
        ) {
            $type = isset($object->configs[self::PARAM_FORM])
                ? self::PARAM_FORM
                : (
                isset($object->configs[self::PARAM_MULTIPART])
                    ? self::PARAM_MULTIPART
                    : $type
                );
        }

        $type = $type ?: self::PARAM_FORM;
        $object->setParamType($type);
        $object->configs[$type] = $params;
        return $object->buildConfigClient();
    }

    /**
     * Remove All existing Parameter
     *
     * @param  string|null $paramName
     * @return static
     */
    public function withoutParam($paramName = null)
    {
        $object = clone $this;
        if (is_null($paramName)) {
            unset($object->configs[$this->currentParamType]);
        } else {
            if (isset($object->configs[$this->currentParamType])) {
                if (!is_array($object->configs[$this->currentParamType])) {
                    unset($object->configs[$this->currentParamType]);
                } else {
                    if (is_array($paramName)) {
                        $paramName = [$paramName];
                    }
                    foreach ($paramName as $paramKey) {
                        if (is_string($paramKey) || is_numeric($paramKey)) {
                            unset($object->configs[$this->currentParamType][$paramName]);
                        }
                    }
                }
            }
        }

        return $object->buildConfigClient();
    }

    /**
     * Set Parameter
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setParam($name, $value)
    {
        if (!isset($this->configs[$this->currentParamType])) {
            $this->configs[$this->currentParamType] = [];
        }

        $this->configs[$this->currentParamType][$name] = $value;
        if (!$this->inProcessingLoop) {
            return $this->buildConfigClient();
        }

        return $this;
    }

    /**
     * Set Params
     *
     * @param array $params
     * @return static
     */
    public function setParams(array $params)
    {
        $this->configs[$this->currentParamType] = [];
        return $this->replaceParams($params);
    }

    /**
     * Set Params
     *
     * @param array $params
     * @return static
     */
    public function replaceParams(array $params)
    {
        if (!isset($this->configs[$this->currentParamType])) {
            $this->configs[$this->currentParamType] = [];
        }

        $this->inProcessingLoop = true;
        foreach ($params as $key => $paramValue) {
            $this->setParam($key, $paramValue);
        }

        $this->inProcessingLoop = false;

        return $this->buildConfigClient();
    }

    /**
     * Set Param Type
     *
     * @param string $type
     * @return static
     * @throws InvalidArgumentException
     */
    public function setParamType($type)
    {
        if (! is_string($type) || ! in_array($type, [self::PARAM_MULTIPART, self::PARAM_FORM])
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid parameter form type, form type only allowed $1s and $2s",
                    self::PARAM_FORM,
                    self::PARAM_MULTIPART
                ),
                E_USER_ERROR
            );
        }

        $this->currentParamType = $type;
        $reverse_params = $type == self::PARAM_FORM
            ? self::PARAM_MULTIPART
            : self::PARAM_FORM;
        $reverse_params_value = isset($this->configs[$reverse_params])
            ? $this->configs[$reverse_params]
            : null;
        $params_value = isset($this->configs[$type])
            ? $this->configs[$type]
            : null;
        unset(
            $this->configs[self::PARAM_FORM],
            $this->configs[self::PARAM_MULTIPART]
        );

        $this->configs[$type] = is_array($reverse_params_value)
            ? $reverse_params_value
            : (is_array($params_value) ? $params_value : []);

        return $this->buildConfigClient();
    }

    /**
     * With URI
     *
     * @param string|UriInterface $uri
     * @return static
     * @throws InvalidArgumentException
     */
    public function withUri($uri)
    {
        if (! $uri instanceof UriInterface) {
            if (!is_string($uri)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Parameter uri must be as string or instance of %s',
                        UriInterface::class
                    ),
                    E_USER_ERROR
                );
            }

            $uri = new Uri($uri);
        }
        $object = clone $this;
        return $this
            ->buildConfigClient()
            ->withRequest($object->request->withUri($uri));
    }

    /**
     * With Set Request
     *
     * @param RequestInterface $request
     * @return mixed
     */
    public function withRequest(RequestInterface $request)
    {
        $object = clone $this;
        $object->request = $request;
        $object->method  = $request->getMethod();
        return $object;
    }

    /**
     * With Method
     *
     * @param string $method GET|PUT|HEAD|POST ... etc
     *                       fallback to default
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function withMethod($method)
    {
        $object = clone $this;
        /**
         *  Check available Method
         */
        $old_method  = $method;
        if (($method = $object->allowedMethod($method)) === false || !is_string($method)) {
            settype($old_method, 'string');
            throw new InvalidArgumentException(
                sprintf(
                    'Method %s is not Allowed!',
                    $old_method
                ),
                E_USER_ERROR
            );
        }

        return $object->withRequest(
            $object->request->withMethod($method)
        );
    }

    /**
     * @return TransportResponse
     */
    public function send()
    {
        try {
            $this->last_response = new TransportResponse(
                $this->client->send($this->request, $this->configs)
            );
        } catch (\Exception $e) {
            $this->last_response = new TransportResponse($e);
        }

        return $this->last_response;
    }

    /**
     * Call Static Method
     *
     * @param string $name      Method
     * @param array  $arguments Argument array
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function __callStatic($name, array $arguments)
    {
        if (empty($arguments)) {
            throw new InvalidArgumentException(
                'Arguments Could not be empty',
                E_USER_ERROR
            );
        }

        $arguments = array_values($arguments);
        if (!is_string($arguments[0]) && ! $arguments[0] instanceof UriInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument 1 must be as a string %s or %s given',
                    UriInterface::class,
                    gettype($arguments[0])
                ),
                E_USER_ERROR
            );
        }

        $config = [];
        if (isset($arguments[1])) {
            if (!is_array($arguments[1])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Argument 2 must be as a array %s or %s given',
                        UriInterface::class,
                        gettype($arguments[1])
                    ),
                    E_USER_ERROR
                );
            }

            $config = $arguments[1];
        }

        $class = new static($arguments[0], $config);

        return $class->withMethod($name);
    }

    /**
     * Magic Method
     *
     * @param string $method
     * @param array  $arguments
     * @return static
     * @throws InvalidArgumentException
     */
    public function __call($method, array $arguments)
    {
        if (empty($arguments)) {
            throw new \InvalidArgumentException(
                'Arguments Could not be empty',
                E_USER_ERROR
            );
        }

        $arguments = array_values($arguments);
        if (!is_string($arguments[0]) && ! $arguments[0] instanceof UriInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument 1 must be as a string %s or %s given',
                    UriInterface::class,
                    gettype($arguments[0])
                ),
                E_USER_ERROR
            );
        }

        if (isset($arguments[1]) && !is_array($arguments[1])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument 2 must be as an array %s given',
                    gettype($arguments[1])
                ),
                E_USER_ERROR
            );
        }

        $transport = $this->withMethod($method)->withUri($arguments[0]);
        if (isset($arguments[1])) {
            foreach ($arguments[1] as $key => $value) {
                $transport->setConfig($key, $value);
            }
        }

        return $transport;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ! $this->last_response instanceof TransportResponse
            ? $this->send()->getResponseBodyString()
            : $this->last_response->getResponseBodyString();
    }
}
