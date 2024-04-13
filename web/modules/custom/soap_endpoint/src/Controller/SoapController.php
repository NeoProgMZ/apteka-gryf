<?php

/**
 * SOAP requests controller class.
 */

namespace Drupal\soap_endpoint\Controller;

use SoapFault;
use SoapServer;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;

use Drupal\soap_endpoint\Client\Process;
use SoapClient;

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
     * @return Response A response object or FALSE on failure.
     */
    public function soap()
    {
        // Get the Symfony request component so that we can adapt the page request
        // accordingly.
        $request = $this->request->getCurrentRequest();
        $response = new Response();
        // $logName = 'test.log';

        // Respond appropriately to the different HTTP verbs.
        switch ($request->getMethod()) {
            case 'GET':
                // // @DEBUG !->
                // $soapServer = new SoapClient($this->getWsdlDocPath());
                // print_r($soapServer->__getFunctions());
                // exit();
                // // @DEBUG !<-
                // This is a get request, so we handle it by returning a WSDL file.
                $wsdl = file_get_contents($this->getWsdlDocPath());
                // Return the WSDL file as output.
                $response->setContent($wsdl);
                // $logName = 'test2.log';
                break;
            case 'POST':
                $result = $this->handleSoapRequest();
                // Return the response from the SOAP request.
                $response->setContent($result);
                break;
            default:
                // Not a GET or a POST request, return a 404.
                throw new NotFoundHttpException();
        }

        $response->headers->set(
            'Content-type',
            'application/xml; charset=windows-1250'
        );

        return $response;
    }

    /**
     * Manage SOAP request.
     *
     * @return string|SoapFault
     *
     * @throws ContainerNotInitializedException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws ConflictingHeadersException
     * @throws SuspiciousOperationException
     */
    protected function handleSoapRequest()
    {
        try {
            // Create some options for the SoapServer.
            $serverOptions = [
                'encoding' => 'windows-1250',
                'wsdl_cache_enabled' => 1,
                'wsdl_cache' => WSDL_CACHE_DISK,
                'wsdl_cache_ttl' => 604800,
                'wsdl_cache_limit' => 10,
                'send_errors' => true, // @FIXME change to false in production!
            ];

            // Instantiate the SoapServer.
            $soapServer = new SoapServer($this->getWsdlDocPath(), $serverOptions);
            $soapServer->setClass(Process::class);
            // Turn output buffering on.
            ob_start();
            // Handle the SOAP request.
            $soapServer->handle();
            // Get the buffers contents.
            $result = ob_get_contents();
            // Removes topmost output buffer.
            ob_end_clean();
            // @QUESTION is this needed in production??
            header_remove();
            // Send back the result.
            return $result;
        } catch (\Exception $exc) {
            // Then return a SoapFault object as the result.
            $soap_fault = new \SoapFault($exc->getCode(), $exc->getMessage());

            return $soap_fault;
        }
    }

    /**
     * Return WSDL document path.
     *
     * @return string
     */
    private function getWsdlDocPath(): string
    {
        // @QUESTION how to handle this better?
        return DRUPAL_ROOT . '/modules/custom/soap_endpoint/wsdl.wsdl';
    }
}
