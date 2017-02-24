<?php
namespace Apatis\Transporter;

/**
 * Class Transport
 * @package Apatis\Transporter
 */
class Transport extends AbstractTransport
{
    /**
     * Retrieve Transport with header like a browser uses
     *
     * @return static
     */
    public function withBrowser()
    {
        $object = clone $this;
        // browser manipulation
        return $object->replaceHeaders(
            [
                'User-Agent'      => TransportUtil::getBrowserUserAgentGenerated(),
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection'      => 'keep-alive',
                'Pragma'          => 'no-cache',
                'Cache-Control'   => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
            ]
        );
    }

    /**
     * Retrieve Transport with Default Header set
     *
     * @return Transport
     */
    public function withoutBrowser()
    {
        $object = clone $this;
        return $object->removeHeaders(
            [
                'Accept',
                'Accept-Encoding',
                'Accept-Language',
                'Connection',
                'Pragma',
                'Upgrade-Insecure-Requests'
            ]
        );
    }
}
