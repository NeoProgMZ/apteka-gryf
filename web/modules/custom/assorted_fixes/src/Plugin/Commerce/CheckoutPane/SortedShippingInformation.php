<?php

namespace Drupal\assorted_fixes\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_pickup\Plugin\Commerce\CheckoutPane\PickupShippingInformation;

/**
 * Provides the pickupable shipping information pane.
 *
 * Collects the pickup shipping profile, then the information for each shipment.
 * Assumes that all shipments share the same shipping profile.
 */
class SortedShippingInformation extends PickupShippingInformation
{

    /**
     * {@inheritdoc}
     */
    public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form)
    {
        $form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

        $form['#type'] = 'container';
        $form['shipping_profile']['#type'] = 'fieldset';
        $form['shipping_profile']['#title'] = $form['#title'];

        $tmp = [
            'removed_shipments' => $form['removed_shipments'],
            'shipments' => $form['shipments'],
            'previous_rate' => $form['previous_rate'],
            'shipping_profile' => $form['shipping_profile'],
            'recalculate_shipping' => $form['recalculate_shipping'],
        ];

        unset(
            $form['removed_shipments'],
            $form['shipments'],
            $form['previous_rate'],
            $form['shipping_profile'],
            $form['recalculate_shipping']
        );

        $form = array_merge($form, $tmp);

        return $form;
    }
}
