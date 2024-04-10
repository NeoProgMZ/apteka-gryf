<?php
/**
 * SOAP requests handler class.
 */
namespace Drupal\soap_endpoint\Client;

use SoapFault;

/**
 * SOAP handler.
 */
class Processor
{
    /**
     * Magic method to call the class.
     *
     * @param mixed $method_name Name of the method requested.
     * @param array $args        Parameters passed to the method.
     *
     * @return array|SoapFault|void
     */
    public function __call($method_name, array $args)
    {
        // print_r($method_name);
        // print_r("\n");
        // print_r($args);
        // exit();
        // @TODO authenticate using $args[0] as username and $args[1] as password.
        try {
            switch ($method_name) {
            case 'SetOffer':
                $object = new SetOffer($args[2]);
                print_r($object);
                exit();
                return [$object];
                break;
            }

        }
        catch (\Exception $e) {
            // Something went wrong, so return a SoapFault.
            return new \SoapFault(0, $e->getMessage(), null);
        }
    }
}