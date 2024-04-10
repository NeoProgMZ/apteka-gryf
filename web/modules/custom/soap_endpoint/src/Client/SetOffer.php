<?php
/**
 * SOAP requests handler class.
 */
namespace Drupal\soap_endpoint\Client;

use DrupalCodeGenerator\Command\Form\Simple;
use SimpleXMLElement;
use SoapFault;

/**
 * SOAP handler.
 */
class SetOffer
{

    /**
     * XML data.
     *
     * @var SimpleXMLElement
     */
    private $xml = '';

    public function __construct(string $xml)
    {
        $this->xml = simplexml_load_string(htmlspecialchars_decode($xml));
    }

    public function getXml(): SimpleXMLElement
    {
        return $this->xml;
    }
}