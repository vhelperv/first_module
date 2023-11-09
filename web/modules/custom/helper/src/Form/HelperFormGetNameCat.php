<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
//use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\InvokeCommand;
//use Drupal\Core\Database\Database;

class HelperFormGetNameCat extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cats_form';
  }

  /**
   * {@inheritdoc}
   */
  /*FormAjaxResponseBuilder*/
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = [
      '#type' => 'form',
      '#ajax' => [
        'callback' => '::validateForm',
        'event' => 'submit',
      ],
    ];
    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('The name must be between 2 and 32 characters long.'),
      '#validation' => TRUE,
      '#maxlength' => 32,
      '#minlength' => 2
    ];
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#description' => $this->t('The email address can only contain Latin letters, the underscore character (_), or the hyphen character (-).'),
      '#prefix' => '<div id="email-field-wrapper">',
      '#suffix' => '</div>',
      '#validation' => TRUE,
      '#pattern' => '/^[a-zA-Z\-_@]+$/',
      '#ajax' => [
        'callback' => '::validateEmail',
        'event' => 'input',
      ],
    ];
    $form['cats_image'] = [
      '#type' => 'managed_file',
      '#title' => $this ->t('Image Upload'),
      '#required' => TRUE,
      '#upload_location' => 'public://cats',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
        'file_validate_size' => [2 * 1024 * 1024],
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Add cat'),
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $catName = $form_state->getValue('cat_name');
    $emailAddress = $form_state->getValue('user_email');
    if (mb_strlen($catName, 'UTF-8') < 2 || mb_strlen($catName, 'UTF-8') > 32) {
      $form_state->setErrorByName('cat_name', $this->t('The cat name must be between 2 and 32 characters long.'));
    }
    if (!preg_match('/^[a-zA-Z\-_@]+$/', $emailAddress)) {
      $form_state->setErrorByName('user_email', $this->t('The email address is invalid.'));
    } elseif (!str_contains($emailAddress, '@')) {
      $form_state->setErrorByName('user_email', $this->t('Email must contain @.'));
    }
  }
  public function validateEmail(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $response = new AjaxResponse();
    $emailAddress = $form_state->getValue('user_email');
    $errorMessageInvalid = '<span id="email-error-message" style="color: red; font-size: 15px;">The email address is invalid.</span>';
    $errorMessageMustContain = '<span id="email-error-message" style="color: red; font-size: 15px;">Email must contain @.</span>';
    // Validate the email address format using a regular expression
    if (!preg_match('/^[a-zA-Z\-_@]+$/', $emailAddress)) {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'addClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
      $response->addCommand(new AppendCommand('#email-field-wrapper .form-type--email', $errorMessageInvalid));
    } elseif (!str_contains($emailAddress, '@')) {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'addClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
      $response->addCommand(new AppendCommand('#email-field-wrapper .form-type--email', $errorMessageMustContain));
    } else {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'removeClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
    }

    return $response;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : AjaxResponse {}
}
