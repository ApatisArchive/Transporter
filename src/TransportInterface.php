<?php
namespace Apatis\Transporter;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Interface TransportInterface
 * @package Apatis\Transporter
 */
interface TransportInterface
{
    /**
     * Default Request Method
     * @const string
     */
    const DEFAULT_METHOD = self::METHOD_GET;

    /**
     * Available Methods
     */
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_COPY    = 'COPY';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_LINK    = 'LINK';
    const METHOD_LOCK    = 'LOCK';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_PURGE   = 'PURGE';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_UNLINK  = 'UNLINK';
    const METHOD_UNLOCK  = 'UNLOCK';
    const METHOD_VIEW    = 'VIEW';
    const METHOD_PROPFIND= 'PROFIND';

    /**
     * @const params for post
     */
    const PARAM_FORM  = 'form_params';
    const PARAM_FILES = 'multipart';
    const PARAM_MULTIPART = self::PARAM_FILES;

    /**
     * @param Client $client
     * @return static
     */
    public function withClient(Client $client);

    /**
     * With Set Headers
     *
     * @param array $headers
     * @return static
     */
    public function withHeaders(array $headers);

    /**
     * With Added Current Headers
     *
     * @param array $headers
     * @return static
     */
    public function withAddedHeaders(array $headers);

    /**
     * With Added Current Headers
     *
     * @param mixed $header
     * @return static
     */
    public function withoutHeader($header);

    /**
     * @param CookieJarInterface $cookieJar
     * @return static
     */
    public function withCookieJar(CookieJarInterface $cookieJar);

    /**
     * @param array $cookie
     * @param string $domain
     * @return static
     */
    public function withCookieArray(array $cookie, $domain);

    /**
     * Without Send Cookie
     *
     * @param string|array $cookieName
     * @return static
     */
    public function withoutCookie($cookieName = null);

    /**
     * With Method
     *
     * @param string $method GET|PUT|HEAD|POST ... etc
     *                       fallback to default
     *
     * @return static
     */
    public function withMethod($method);

    /**
     * With Params
     *
     * @param array $params array parameter to set
     * @param string $type default use TransportInterface::PARAM_FORM
     * @return static
     */
    public function withParams(array $params = [], $type = null);

    /**
     * Remove All existing Parameter
     *
     * @param  string|null $paramName
     * @return static
     */
    public function withoutParam($paramName = null);

    /**
     * Set Parameter
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function setParam($name, $value);

    /**
     * Set Params
     *
     * @param array $params
     * @return mixed
     */
    public function setParams(array $params);

    /**
     * Set Params
     *
     * @param array $params
     * @return mixed
     */
    public function replaceParams(array $params);

    /**
     * @param string $type
     * @return mixed
     */
    public function setParamType($type);

    /**
     * With URI
     *
     * @param string|UriInterface $uri
     * @return static
     */
    public function withUri($uri);

    /**
     * With Set Request
     *
     * @param RequestInterface $request
     * @return mixed
     */
    public function withRequest(RequestInterface $request);

    /**
     * @return TransportResponse
     */
    public function send();
}
