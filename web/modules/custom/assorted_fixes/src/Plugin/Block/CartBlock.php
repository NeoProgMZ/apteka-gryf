<?php

namespace Drupal\assorted_fixes\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_cart_flyout\Plugin\Block\CartBlock as BaseCartBlock;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "custom_commerce_cart_flyout",
 *   admin_label = @Translation("Custom Cart Flyout"),
 *   category = @Translation("Commerce")
 * )
 */
class CartBlock extends BaseCartBlock
{

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $form = parent::blockForm($form, $form_state);
        $form['use_amount'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Use total amount.'),
            '#description' => $this->t('Instead of counting the items use total amount from the cart.'),
            '#default_value' => $this->configuration['use_amount'] ?? false,
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        parent::blockSubmit($form, $form_state);
        $this->configuration['use_amount'] = $form_state->getValue('use_amount');
    }
    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $icon_path = \Drupal::theme()->getActiveTheme()->getPath() . '/icons/cart.png';

        return [
            '#attached' => [
                'library' => [
                    'commerce_cart_flyout/flyout',
                ],
                'drupalSettings' => [
                    'cartFlyout' => [
                        'use_quantity_count' => $this->configuration['use_quantity_count'],
                        'use_amount' => $this->configuration['use_amount'] ?? false,
                        'templates' => [
                            'icon' => $this->renderTemplate('commerce_cart_flyout_block_icon'),
                            'block' => $this->renderTemplate('commerce_cart_flyout_block'),
                            'offcanvas' => $this->renderTemplate('commerce_cart_flyout_offcanvas'),
                            'offcanvas_contents' => $this->renderTemplate('commerce_cart_flyout_offcanvas_contents'),
                            'offcanvas_contents_items' => $this->renderTemplate('commerce_cart_flyout_offcanvas_contents_items'),
                        ],
                        'url' => Url::fromRoute('commerce_cart.page')->toString(),
                        'icon' => $this->fileUrlGenerator->generateString($icon_path),
                    ],
                ],
            ],
            '#markup' => Markup::create('<div class="cart-flyout"></div>'),
        ];
    }
}