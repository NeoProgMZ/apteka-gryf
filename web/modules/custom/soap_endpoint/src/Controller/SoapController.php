<?php

/**
 * SOAP requests controller class.
 */

namespace Drupal\soap_endpoint\Controller;

use SoapFault;
use SoapServer;

use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;

use Drupal\soap_endpoint\Client\Processor;

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
     * @return bool|Response A response object or FALSE on failure.
     */
    public function soap()
    {
        // Get the Symfony request component so that we can adapt the page request
        // accordingly.
        $request = $this->request->getCurrentRequest();

        // Respond appropriately to the different HTTP verbs.
        switch ($request->getMethod()) {
        case 'GET':
            // $client = new SoapClient($this->getWsdlDocPath(), ['location' => 'https://apteka.ddev.site/services/sync/soap']);
            // print_r('<pre>');
            // print_r(
            //     $client->__soapCall(
            //         'SetOffer', ['AUserName' => 'mz', 'APassword' => 'WUT!?', 'AOffer' => 'some xml']
            //     )
            // );
            // exit();
            // This is a get request, so we handle it by returning a WSDL file.
            $wsdl = file_get_contents($this->getWsdlDocPath());
            // Return the WSDL file as output.
            $response = new Response($wsdl);
            $response->headers->set(
                'Content-type',
                'application/xml; charset=utf-8'
            );
            return $response;

        case 'POST':
            // print_r('<pre>');
            // print_r($request);
            // exit();
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
        try {
            // Create some options for the SoapServer.
            $serverOptions = [
                'encoding' => 'UTF-8',
                'wsdl_cache_enabled' => 1,
                'wsdl_cache' => WSDL_CACHE_DISK,
                'wsdl_cache_ttl' => 604800,
                'wsdl_cache_limit' => 10,
                'send_errors' => true, // @FIXME change to false in production!
            ];

            // Instantiate the SoapServer.
            $soapServer = new SoapServer($this->getWsdlDocPath(), $serverOptions);
            $soapServer->setClass(Processor::class);
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
            \Drupal::logger('soap_endpoint')->error(
                'soap error ' . $exc->getMessage()
            );
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