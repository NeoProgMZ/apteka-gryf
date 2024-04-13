<?php

/**
 * SOAP requests handler class.
 */

namespace Drupal\soap_endpoint\Client;

use SoapFault;

use Exception;

enum AllowedMethods: string
{
    case SetOffer = "SetOffer";
    case SetOrders = "SetOrders";
    case GetOrders = "GetOrders";
}

/**
 * SOAP handler.
 */
class Process
{
    /**
     * Magic method to call the class.
     *
     * @param mixed $methodName Name of the method requested.
     * @param array $args        Parameters passed to the method.
     *
     * @return array|SoapFault|void
     */
    public function __call($methodName, array $args)
    {
        try {
            if (false === \Drupal::service('user.auth')->authenticate($args[0], $args[1])) {
                throw new Exception("Authentication failed");
            }

            $methodEnum = AllowedMethods::tryFrom($methodName);
            $handler = null;

            switch ($methodEnum) {
                case AllowedMethods::SetOffer:
                    $handler = new SetOffer($args[2]);
                    break;
                case AllowedMethods::SetOrders:
                    $handler = new SetOrders($args[2]);
                    break;
                case AllowedMethods::GetOrders:
                    $handler = new GetOrders($args[2]);
                    break;
                default:
                    throw new Exception('Unknown method');
            }

            return $handler->process();
        } catch (Exception $exc) {
            // Something went wrong, so return a SoapFault.
            return new SoapFault('SOAP-ENV:Client', $exc->getMessage(), $methodName);
        }
    }
}