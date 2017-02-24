<?php
namespace Apatis\Transporter;

/**
 * Class TransportUtil
 * @package Apatis\Transporter
 */
class TransportUtil
{
    /**
     * @const string default user agent
     */
    const DEFAULT_USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:51.0) Gecko/20100101 Firefox/51.0';

    /**
     * Get generate User Agent
     *
     * @return string
     */
    public static function getBrowserUserAgentGenerated()
    {
        static $ua;
        if (isset($ua)) {
            return $ua;
        }
        $year  = abs(@date('Y'));
        if ($year <= 2017) {
            return $ua = self::DEFAULT_USER_AGENT;
        }

        $user_agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:[version].0) Gecko/20100101 Firefox/[version].0';
        $month      = abs(@date('m'));
        $version    = 50;
        $version   += (($year-2017) - (6 % $month === 0 ? 1 : 0));
        return $ua = str_replace('[version]', $version, $user_agent);
    }
}
