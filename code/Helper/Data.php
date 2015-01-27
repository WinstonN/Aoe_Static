<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Registry Key to store individual max-age times
     */
    const REGISTRY_MAX_AGE = 'aoe_static_max_age';

    /**
     * computes the minimum max-age timestamp, based on the given timestamps and a possible earlier-set timestamp
     *
     * @param array|int $timestamps
     * @return Aoe_Static_Helper_Data
     */
    public function computeRegistryMaxAge($timestamps)
    {
        $maxAge = -1;
        if (!is_array($timestamps)) {
            $timestamps = array($timestamps);
        }

        foreach ($timestamps as $timestamp) {
            if (($timestamp > 0) && (($timestamp < $maxAge) || ($maxAge < 0))) {
                $maxAge = $timestamp;
            }
        }
        if ($timestamp = Mage::registry(self::REGISTRY_MAX_AGE)) {
            if (($timestamp > 0) && (($timestamp < $maxAge) || ($timestamp < 0))) {
                $maxAge = $timestamp;
            }
            Mage::unregister(self::REGISTRY_MAX_AGE);
        }
        Mage::register(self::REGISTRY_MAX_AGE, $maxAge);

        return $this;
    }
    /**
     * helper method to ban varnish objects based on a simple match 
     * of x-magento-cache-tag headers
     * @param str $varnishNode
     * @param str $host
     * @param str $tag
     * @param str $requestMethod
     * @see Aoe_Static_Model_Observer::_applBlockCacheTags
     * @return bool
     */
    public function banRequestByTag($varnishNode, $host, $tag, $requestMethod = 'BAN')
    {
        $result = false;
        $client = new Varien_Http_Client($varnishNode);
        $client->setMethod($requestMethod);
        $client->setHeaders('x-magento-cache-tag-invalidate', $tag);
        $client->setHeaders('host', $host);
        try{
            $response = $client->request();
            if ($response->isSuccessful()) {
                $result = true;
            }
        } catch (Exception $e) {}
        return $result;
    }
    /**
     * ban a given URL in varnish
     * @param str $varnishNode
     * @param str $url
     * @param str $requestMethod
     * @return boolean
     */
    public function banUrl($varnishNode, $url, $requestMethod = 'BAN')
    {
        $result   = false;
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $urlParts = parse_url($url);
            $client   = new Zend_Http_Client(str_replace($urlParts['host'], $varnishNode, $url));
            $client->setHeaders('host', $urlParts['host']);
            try {
                $response = $client->request($requestMethod);
                if ($response->isSuccessful()) {
                    $result = true;
                }
            } catch (Exception $e) {
            }
        }
        return $result;
    }
}
