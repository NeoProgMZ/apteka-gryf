<?php

namespace Drupal\commerce_payu\Plugin\Commerce\PaymentGateway;

use OpenPayU_Order;
use OpenPayU_Exception;
use OpenPayU_Configuration;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;

/**
 * Provides the PayU offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payu_redirect_checkout",
 *   label = @Translation("PayU (Redirect to quickpay)"),
 *   display_label = @Translation("PayU"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payu\PluginForm\PayuPaymentForm",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase
{
    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
            'pos_id' => 0,
            'signature' => '',
            'client_id' => 0,
            'client_secret' => '',
        ] + parent::defaultConfiguration();
    }


    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['pos_id'] = [
            '#type' => 'number',
            '#title' => $this->t('POS Id'),
            '#description' => $this->t('Merchant main id.'),
            '#default_value' => $this->configuration['pos_id'],
            '#required' => TRUE,
        ];

        $form['signature'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Second key'),
            '#description' => $this->t('Merchant secret.'),
            '#default_value' => $this->configuration['signature'],
            '#required' => TRUE,
        ];

        $form['client_id'] = [
            '#type' => 'number',
            '#title' => $this->t('Client Id'),
            '#description' => $this->t('OAuth Client Id.'),
            '#default_value' => $this->configuration['client_id'],
            '#required' => TRUE,
        ];

        $form['client_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Client Secret'),
            '#description' => $this->t('OAuth Client Secret.'),
            '#default_value' => $this->configuration['client_secret'],
            '#required' => TRUE,
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        $values = $form_state->getValue($form['#parents']);
        $this->configuration['pos_id'] = $values['pos_id'];
        $this->configuration['signature'] = $values['signature'];
        $this->configuration['client_id'] = $values['client_id'];
        $this->configuration['client_secret'] = $values['client_secret'];
    }

    /**
     * {@inheritdoc}
     */
    public function onReturn(OrderInterface $order, Request $request)
    {
        $this->prep_client();

        $order = OpenPayU_Order::retrieve($order);

        try {
            if ($order->getStatus() == 'SUCCESS') {
                // @TODO handle data.
            }
        } catch (OpenPayU_Exception $exc) {
            throw new PaymentGatewayException($exc->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onNotify(Request $request)
    {
        if ($request->isMethod('POST') === false) {
            return;
        }

        $this->prep_client();

        $data = $request->getContent(false);

        try {
            if (!empty($data)) {
                $result = OpenPayU_Order::consumeNotification($data);
            }

            if (false === empty($result->getResponse()->order->orderId)) {
                /* Check if OrderId exists in Merchant Service, update Order data by OrderRetrieveRequest */
                $order = OpenPayU_Order::retrieve($result->getResponse()->order->orderId);

                if ($order->getStatus() == 'SUCCESS') {
                    // @TODO handle payment!
                    //the response should be status 200
                    header("HTTP/1.1 200 OK");
                }
            }
        } catch (OpenPayU_Exception $exc) {
            throw new PaymentGatewayException($exc->getMessage());
        }
    }

    /**
     * Pre configure client.
     * 
     * @return void 
     */
    private function prep_client()
    {
        //set Production Environment
        OpenPayU_Configuration::setEnvironment(
            ($this->configuration['mode'] === 'Live') ? 'secure' : 'sandbox'
        );
        //set POS ID and Second MD5 Key (from merchant admin panel)
        OpenPayU_Configuration::setMerchantPosId($this->configuration['pos_id']);
        OpenPayU_Configuration::setSignatureKey($this->configuration['signature']);

        //set Oauth Client Id and Oauth Client Secret (from merchant admin panel)
        OpenPayU_Configuration::setOauthClientId($this->configuration['client_id']);
        OpenPayU_Configuration::setOauthClientSecret($this->configuration['client_secret']);
    }
}