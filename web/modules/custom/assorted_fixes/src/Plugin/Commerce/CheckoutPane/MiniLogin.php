<?php

namespace Drupal\assorted_fixes\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login;
use Drupal\Core\Entity\EntityFormBuilder;

/**
 * Provides the login pane.
 *
 * @CommerceCheckoutPane(
 *   id = "login",
 *   label = @Translation("Login or continue as guest"),
 *   default_step = "login",
 * )
 */
class MiniLogin extends Login
{

    /**
     * {@inheritdoc}
     */
    public function buildPaneForm(
        array $pane_form,
        FormStateInterface $form_state,
        array &$complete_form
    ) {
        $pane_form['#attributes']['data-vertical-tabs-panes'] = '';

        $pane_form['#attached']['library'][] = 'commerce_checkout/login_pane';

        $pane_form['authentication_tabs'] = [
            '#type' => 'vertical_tabs',
            '#default_tab' => 'guest',
        ];

        $pane_form['register'] = [
            '#parents' => array_merge($pane_form['#parents'], ['register']),
            '#type' => 'details',
            '#title' => $this->t('New Customer'),
            '#access' => $this->configuration['allow_registration'],
            '#attributes' => [
                'class' => [
                    'form-wrapper__login-option',
                    'form-wrapper__guest-checkout',
                ],
            ],
            '#group' => 'authentication_tabs',
            '#collapsible' => TRUE,
        ];
        $pane_form['register']['mail'] = [
            '#type' => 'email',
            '#title' => $this->t('Email address'),
            '#required' => FALSE,
        ];
        $pane_form['register']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Username'),
            '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
            '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
            '#required' => FALSE,
            '#attributes' => [
                'class' => ['username'],
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
                'spellcheck' => 'false',
            ],
            '#default_value' => '',
        ];
        $pane_form['register']['password'] = [
            '#type' => 'password_confirm',
            '#size' => 60,
            '#description' => $this->t('Provide a password for the new account in both fields.'),
            '#required' => FALSE,
        ];
        $pane_form['register']['register'] = [
            '#type' => 'submit',
            '#value' => $this->t('Create account and continue'),
            '#op' => 'register',
            '#weight' => 50,
        ];

        // $rform = \Drupal::formBuilder()->buildForm(\Drupal\user\AccountForm::class, $form_state);

        // print_r('<pre>');
        // print_r(new \Drupal\user\RegisterForm());
        // exit();

        /** @var \Drupal\user\UserInterface $account */
        $account = $this->entityTypeManager->getStorage('user')->create([]);
        $form_display = EntityFormDisplay::collectRenderDisplay(
            $account,
            $this->configuration['registration_form_mode']
        );

        $form_display->buildForm($account, $pane_form['register'], $form_state);

        $pane_form['returning_customer'] = [
            '#type' => 'details',
            '#title' => $this->t('Returning Customer'),
            '#attributes' => [
                'class' => [
                    'form-wrapper__login-option',
                    'form-wrapper__returning-customer',
                ],
            ],
            '#group' => 'authentication_tabs',
            '#collapsible' => TRUE,
        ];
        $pane_form['returning_customer']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Username'),
            '#size' => 60,
            '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
            '#attributes' => [
                'autocorrect' => 'none',
                'autocapitalize' => 'none',
                'spellcheck' => 'false',
                'autofocus' => 'autofocus',
            ],
        ];
        $pane_form['returning_customer']['password'] = [
            '#type' => 'password',
            '#title' => $this->t('Password'),
            '#size' => 60,
        ];
        $pane_form['returning_customer']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Log in'),
            '#op' => 'login',
            '#attributes' => [
                'formnovalidate' => 'formnovalidate',
            ],
            '#limit_validation_errors' => [
                array_merge($pane_form['#parents'], ['returning_customer']),
            ],
            '#submit' => [],
        ];
        $pane_form['returning_customer']['forgot_password'] = [
            '#type' => 'link',
            '#title' => $this->t('Forgot password?'),
            '#url' => Url::fromRoute('user.pass'),
        ];

        $pane_form['guest'] = [
            '#type' => 'details',
            '#title' => $this->t('Guest Checkout'),
            '#access' => $this->configuration['allow_guest_checkout'],
            '#attributes' => [
                'class' => [
                    'form-wrapper__login-option',
                    'form-wrapper__guest-checkout',
                ],
            ],
            '#group' => 'authentication_tabs',
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
        ];
        $pane_form['guest']['text'] = [
            '#prefix' => '<p>',
            '#suffix' => '</p>',
            '#markup' => $this->t('Proceed to checkout. You can optionally create an account at the end.'),
            '#access' => $this->canRegisterAfterCheckout(),
        ];
        $pane_form['guest']['continue'] = [
            '#type' => 'submit',
            '#value' => $this->t('Continue as Guest'),
            '#op' => 'continue',
            '#attributes' => [
                'formnovalidate' => 'formnovalidate',
            ],
            '#limit_validation_errors' => [],
            '#submit' => [],
        ];

        $pane_form['visibility_tabs']['visibility_tabs__active_tab'] = [
            '#type' => 'hidden',
            '#value' => 'guest',
            '#attributes' => [
                'class' => ['vertical-tabs__active-tab'],
            ],
        ];

        return $pane_form;
    }
}
