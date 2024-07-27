<?php

namespace Drupal\commerce_payu\PluginForm;

use OpenPayU_Util;
use OpenPayU_Order;
use Drupal\Core\Url;
use OpenPayU_Configuration;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;

class PayuPaymentForm extends BasePaymentOffsiteForm
{
    public function __construct()
    {
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        $configuration = $payment_gateway_plugin->getConfiguration();

        //set Production Environment
        OpenPayU_Configuration::setEnvironment(
            $configuration['mode'] === 'Live' ? 'secure' : 'sandbox'
        );

        //set POS ID and Second MD5 Key (from merchant admin panel)
        OpenPayU_Configuration::setMerchantPosId($configuration['pos_id']);
        OpenPayU_Configuration::setSignatureKey($configuration['signature']);

        //set Oauth Client Id and Oauth Client Secret (from merchant admin panel)
        OpenPayU_Configuration::setOauthClientId($configuration['client_id']);
        OpenPayU_Configuration::setOauthClientSecret($configuration['client_secret']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        $order = [];
        $order['continueUrl'] = $form['#return_url'];
        $order['notifyUrl'] = Url::fromRoute(
            'commerce_payment.notify',
            ['commerce_payment_gateway' => 'payu_redirect_checkout'],
            ['absolute' => TRUE]
        );
        $order['customerIp'] = $_SERVER['REMOTE_ADDR'];
        $order['merchantPosId'] = OpenPayU_Configuration::getOauthClientId() ? OpenPayU_Configuration::getOauthClientId() : OpenPayU_Configuration::getMerchantPosId();
        $order['currencyCode'] = $payment->getAmount()->getCurrencyCode();
        $order['totalAmount'] = $payment->getAmount()->getNumber();
        $order['extOrderId'] = $payment->getOrderId(); //must be unique!
        $order['description'] = $this->t('Order #%s', [$order['extOrderId']]);

        $items = $payment->getOrder()->getItems();

        foreach ($items as $index => $item) {
            $order['products'][$index]['name'] = $item->getTitle();
            $order['products'][$index]['unitPrice'] = $item->getUnitPrice();
            $order['products'][$index]['quantity'] = $item->getQuantity();
        }
        //optional section buyer
        $client = $payment->getOrder()->getCustomer();
        $order['buyer']['email'] = $client->getEmail();
        // $order['buyer']['phone'] = '123123123';
        $billing_address = $payment->getOrder()->getBillingProfile()->get('address');
        $order['buyer']['firstName'] = $billing_address->getGivenName();
        $order['buyer']['lastName'] = $billing_address->getFamilyName();

        $response = OpenPayU_Order::create($order);
        $status_desc = OpenPayU_Util::statusDesc($response->getStatus());

        if ($response->getStatus() == 'SUCCESS') {
            return $this->buildRedirectForm(
                $form,
                $form_state,
                $response->getResponse()->redirectUri,
                []
            );
        } else {
            throw new PaymentGatewayException(
                $this->t(
                    'Order placement failed: "%s" (%d)',
                    [
                        $status_desc,
                        $response->getStatus(),
                    ]
                )
            );
        }
    }
}