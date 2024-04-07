<?php

/**
 * SOAP requests controller class.
 */

namespace Drupal\access_soap_module\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Controller\ControllerBase;
use SoapFault;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for SOAP requests.
 */
class SoapController extends ControllerBase
{

    /**
     * Request var.
     *
     * @var RequestStack
     */
    public $request;

    /**
     * SoapController constructor.
     *
     * @param RequestStack $request Request stack.
     */
    public function __construct(RequestStack $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     *
     * @param ContainerInterface $container Container wrapper.
     *
     * @return RequestStack
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('request_stack')
        );
    }

    /**
     * Soap method.
     *
     * @param string $endpoint Endpoint url as a string.
     *
     * @return bool|Response A response object or FALSE on failure.
     */
    public function soap($endpoint)
    {
        // Get the Symfony request component so that we can adapt the page request
        // accordingly.
        $request = $this->request->getCurrentRequest();

        // Respond appropriately to the different HTTP verbs.
        switch ($request->getMethod()) {
            case 'GET':
                // This is a get request, so we handle it by returning a WSDL file.
                $wsdlFileRender = $this->handleGetRequest();

                if ($wsdlFileRender == false) {
                    // If the WSDL file was not returned then we issue a 404.
                    throw new NotFoundHttpException();
                }
                // @FIXME just read and return the fi-in file contents!
                // Render the WSDL file.
                $wsdlFileOutput = \Drupal::service('renderer')->render($wsdlFileRender);

                // Return the WSDL file as output.
                $response = new Response($wsdlFileOutput);
                $response->headers->set(
                    'Content-type',
                    'application/xml; charset=utf-8'
                );
                return $response;

            case 'POST':
                // Handle SOAP Request.
                $result = $this->handleSoapRequest();

                if ($result == false) {
                    // False should only be returned via a non-existent endpoint,
                    // so we return a 404.
                    throw new NotFoundHttpException();
                }

                // Return the response from the SOAP request.
                $response = new Response($result);
                $response->headers->set(
                    'Content-type',
                    'application/xml; charset=utf-8'
                );
                return $response;

            default:
                // Not a GET or a POST request, return a 404.
                throw new NotFoundHttpException();
        }
    }

    protected function handleGetRequest()
    {
    }

    /**
     * Manage SOAP request.
     *
     * @return string|false|SoapFault
     *
     * @throws ContainerNotInitializedException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws ConflictingHeadersException
     * @throws SuspiciousOperationException
     */
    protected function handleSoapRequest()
    {
        // Construct the WSDL file location.
        $wsdlUri = \Drupal::service(
            'extension.list.module'
        )->getPath(
            'soap_endpoint'
        ) . '/wsdl.wsdl';
        $soapClass = 'Drupal\soap_endpoint\Soap\SoapClass';

        try {
            // Create some options for the SoapServer.
            $serverOptions = [
                'encoding' => 'UTF-8',
                'wsdl_cache_enabled' => 1,
                'wsdl_cache' => WSDL_CACHE_DISK,
                'wsdl_cache_ttl' => 604800,
                'wsdl_cache_limit' => 10,
                'send_errors' => false,
            ];

            // Instantiate the SoapServer.
            $soapServer = new \SoapServer($wsdlUri, $serverOptions);
            $soapServer->setClass($soapClass);

            // Turn output buffering on.
            ob_start();
            // Handle the SOAP request.
            $soapServer->handle();
            // Get the buffers contents.
            $result = ob_get_contents();
            // Removes topmost output buffer.
            ob_end_clean();
            // Send back the result.
            return $result;
        } catch (\Exception $exc) {
            // An error happened so we log it.
            \Drupal::logger('access_soap_module')->error(
                'soap error ' . $exc->getMessage()
            );
            // Then return a SoapFault object as the result.
            $soap_fault = new \SoapFault($exc->getCode(), $exc->getMessage());
            return $soap_fault;
        }
    }
}