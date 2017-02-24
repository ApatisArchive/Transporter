<?php
namespace Apatis\Transporter\Test\PhpUnit;

use Apatis\Transporter\TransportUtil;

/**
 * Class TransporterTest
 * @package Apatis\Transporter\Test\PhpUnit
 */
class TransporterTest extends \PHPUnit_Framework_TestCase
{
    public function testUtil()
    {
        $this->assertContains('Mozilla', TransportUtil::getBrowserUserAgentGenerated());
    }
}
