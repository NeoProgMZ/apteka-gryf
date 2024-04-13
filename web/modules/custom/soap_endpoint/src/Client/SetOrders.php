<?php

/**
 * SOAP requests handler class.
 */

namespace Drupal\soap_endpoint\Client;

use SimpleXMLElement;

/**
 * SOAP handler.
 */
class SetOrders
{

    /**
     * Data sent to the service.
     *
     * @var SimpleXMLElement|false
     */
    private $xml = false;

    /**
     * Basic constructor.
     *
     * @param string $xml Data in XML format.
     *
     * @return void
     *
     * @throws Exception On malformed XML.
     */
    public function __construct(string $xml)
    {
        $this->xml = simplexml_load_string(htmlspecialchars_decode($xml));

        if ($this->xml === false) {
            throw new \Exception("Invalid XML");
        }
    }

    /**
     * Perform requested operartions.
     *
     * @return bool
     */
    public function process(): bool
    {
        $result = false;

        if (false === empty($this->xml)) {
            // @TODO process request.
            $result = true;
        }

        return $result;
    }

    /**
     * Return settings used in this operation.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'xml' => $this->xml
        ];
    }
}