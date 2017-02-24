<?php
namespace Apatis\Transporter;

use Apatis\Exceptions\InvalidArgumentException;
use Apatis\Exceptions\UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TransportResponse
 * @package Apatis\Transporter
 */
class TransportResponse
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * TransportResponse constructor.
     * @param ResponseInterface|\Exception $response
     */
    public function __construct($response)
    {
        if ($response instanceof \Exception && $response instanceof ResponseInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid Parameter Response. Response must be an instance of %s or %s.",
                    ResponseInterface::class,
                    \Exception::class
                )
            );
        }

        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->getResponse() instanceof \Exception;
    }

    /**
     * @return bool
     */
    public function isTimeOut()
    {
        return $this->isError()
            && stripos($this->getResponse()->getMessage(), 'Timed Out') !== false;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->getResponse() instanceof ResponseInterface;
    }

    /**
     * Get The Response Response
     *
     * @return \Exception|ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return \Psr\Http\Message\StreamInterface
     * @throws UnexpectedValueException
     */
    public function getResponseBody()
    {
        if ($this->isError()) {
            throw new UnexpectedValueException(
                sprintf(
                    'Result is not a valid response, returning exceptions with : <br/> %s',
                    (string) $this->getResponse()
                ),
                E_WARNING
            );
        }

        return $this->response->getBody();
    }

    /**
     * @return string
     */
    public function getResponseBodyString()
    {
        return (string) $this->getResponseBody();
    }
}
