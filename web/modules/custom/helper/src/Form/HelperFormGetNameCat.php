<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\InvokeCommand;

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
    $form['cat-name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('The name must be between 2 and 32 characters long.'),
      '#validation' => TRUE,
      '#maxlength' => 32,
      '#minlength' => 2
    ];
    $form['user-email'] = [
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
    $form['cats-image'] = [
      '#type' => 'managed_file',
      '#title' => $this ->t('Image Upload'),
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
        'file_validate_size' => [2 * 1024 * 1024],
      ],
      '#ajax' => [
        'callback' => '::validateImage',
        'event' => 'input'
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'submit',
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
    $catName = $form_state->getValue('cat-name');
    $emailAddress = $form_state->getValue('user-email');
    $imageFile = $form_state->getValue('cats-image');
    if (mb_strlen($catName, 'UTF-8') < 2 || mb_strlen($catName, 'UTF-8') > 32) {
      $form_state->setErrorByName('cat-name', $this->t('The cat name must be between 2 and 32 characters long.'));
    }

    if (!preg_match('/^[a-zA-Z\-_@]+$/', $emailAddress)) {
      $form_state->setErrorByName('user-email', $this->t('The email address is invalid.'));
    } elseif (!str_contains($emailAddress, '@')) {
      $form_state->setErrorByName('user-email', $this->t('Email must contain @.'));
    }

    if (empty($imageFile)) {
      $form_state->setErrorByName('cats-image', $this->t('Image upload is required.'));
    } else {
      $file = \Drupal\file\Entity\File::load($imageFile[0]);

      if ($file) {
        $file_size = $file->getSize();

        if ($file_size > 2 * 1024 * 1024) {
          $form_state->setErrorByName('cats-image', $this->t('Image size exceeds 2MB limit.'));
        }
      }
    }
  }
  public function validateEmail(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $response = new AjaxResponse();
    $emailAddress = $form_state->getValue('user-email');
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
  function validateImage(array &$form, FormStateInterface $form_state) {
    // Check if there is an uploaded file
    $uploadedFile = $form_state->getValue('cats-image');
    if (!$uploadedFile || !is_array($uploadedFile) || !isset($uploadedFile['file'])) {
      return 0;
    }

    // Validate the file extension
    $file = $uploadedFile['file'];
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($file->getMimeType(), $allowedExtensions)) {
      $errorMessage = t('The selected file is not a valid image. The allowed file formats are: jpg, jpeg, and png.');
      $form['cats-image']['#ajax']['error'] = $errorMessage;
      return $errorMessage;
    }

    // Validate the file size
    $maxFileSize = 2048; // 2 MB
    if ($file->getSize() > $maxFileSize) {
      $errorMessage = t('The selected file is too large. The maximum file size is: %max_size% MB', ['max_size' => $maxFileSize / 1024]);
      $form['cats-image']['#ajax']['error'] = $errorMessage;
      return $errorMessage;
    }

    // No validation errors found, so return null
    return 0;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $name = $form_state->getValue('cat-name');
    $response = new AjaxResponse();
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 32) {
      $response->addCommand(new MessageCommand('The name must be between 2 and 32 characters long.', NULL, ['type' => 'error']));
    }
    else {
      $response->addCommand(new MessageCommand('The name is valid.', NULL));
    }
    return $response;
  }
}
