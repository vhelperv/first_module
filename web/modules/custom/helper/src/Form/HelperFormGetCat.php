<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Class HelperFormGetCat.
 *
 * @package Drupal\helper\Form
 */
class HelperFormGetCat extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cats_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Cat name textfield
    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#description' => $this->t('The name must be between 2 and 32 characters long.'),
      '#suffix' => '<div id="name-field-wrapper" class="error"></div>',
      '#ajax' => [
        'callback' => '::validateName',
        'event' => 'input',
      ],
    ];

    // User email field with Ajax validation
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#description' => $this->t('The email address can only contain Latin letters, the underscore character (_), or the hyphen character (-).'),
      '#element_validate' => [[$this, 'submitForm']],
      '#suffix' => '<div id="email-field-wrapper" class="error"></div><div id="email-field"></div>',
      '#ajax' => [
        'callback' => '::validateEmail',
        'event' => 'input',
      ],
    ];

    // Cats image managed file field
    $form['cats_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image Upload'),
      '#multiple' => FALSE,
      '#description' => $this->t('Add a photo of a cat with the extension jpg, jpeg, or png. The maximum size is 2MB'),
      '#upload_location' => 'public://cats',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
        'file_validate_size' => [2 * 1024 * 1024],
      ],
    ];

    // Form actions
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Ajax callback to validate the cat name.
   */
  public function validateName(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $catName = $form_state->getValue('cat_name');

    // Check if cat name length is within the valid range
    if (mb_strlen($catName, 'UTF-8') < 2 || mb_strlen($catName, 'UTF-8') > 32) {
      $response->addCommand(new MessageCommand('The cat name must be between 2 and 32 characters long.', NULL, ['type' => 'error'], TRUE));
    } else {
      $response->addCommand(new MessageCommand('Name is valid', NULL, ['type' => 'status'], TRUE));
    }

    return $response;
  }

  /**
   * Ajax callback to validate the user email.
   */
  public function validateEmail(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $emailAddress = $form_state->getValue('user_email');

    // Error messages for different validation scenarios
    $errorMessageInvalid = '<span id="email-error-message" style="color: red; font-size: 15px;">The email address is invalid.</span>';
    $errorMessageMustContain = '<span id="email-error-message" style="color: red; font-size: 15px;">Email must contain @.</span>';
    $errorMessageMissingDomain = '<span id="email-error-message" style="color: red; font-size: 15px;">A domain is required. For example: @gmail.com</span>';

    // Validate the email address format using a regular expression
    if (!preg_match('/^[a-zA-Z\-_@.]+$/', $emailAddress)) {
      $this->addEmailErrorCommands($response, $errorMessageInvalid);
    } elseif (!str_contains($emailAddress, '@')) {
      $this->addEmailErrorCommands($response, $errorMessageMustContain);
    } elseif (substr($emailAddress, -1) === '@') {
      $this->addEmailErrorCommands($response, $errorMessageMissingDomain);
    } else {
      $this->removeEmailErrorCommands($response);
    }

    return $response;
  }

  /**
   * Helper method to add email error commands.
   */
  private function addEmailErrorCommands(AjaxResponse &$response, $errorMessage): void {
    $response->addCommand(new InvokeCommand('#edit-user-email', 'addClass', ['error']));
    $response->addCommand(new RemoveCommand('#email-error-message'));
    $response->addCommand(new AppendCommand('#email-field', $errorMessage));
  }

  /**
   * Helper method to remove email error commands.
   */
  private function removeEmailErrorCommands(AjaxResponse &$response): void {
    $response->addCommand(new InvokeCommand('#edit-user-email', 'removeClass', ['error']));
    $response->addCommand(new RemoveCommand('#email-error-message'));
  }

  /**
   * Ajax callback to handle form submission.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    // Process form submission
    $values = $form_state->getValues();
    $catName = $values['cat_name'];
    $userEmail = $values['user_email'];
    $file_upload = $values['cats_image'];
    $flag = TRUE;

    // Validate cat name and user email
    $response = new AjaxResponse();
    if (trim($catName) == '') {
      $flag = FALSE;
      $response->addCommand(new HtmlCommand('#name-field-wrapper', 'Please enter the cat`s nickname'));
    } elseif (mb_strlen($values['cat_name'], 'UTF-8') < 2 || mb_strlen($catName, 'UTF-8') > 32) {
      $flag = FALSE;
    }
    if (trim($userEmail) == '') {
      $flag = FALSE;
      $response->addCommand(new HtmlCommand('#email-field-wrapper', 'Please enter your email'));
    } elseif (!preg_match('/^[a-zA-Z\-_@.]+$/', $userEmail)) {
      $flag = FALSE;
    } elseif (!str_contains($userEmail, '@')) {
      $flag = FALSE;
    } elseif (substr($userEmail, -1) === '@') {
      $flag = FALSE;
    }

    // Validate file upload
    if (empty($file_upload[0])) {
      $flag = FALSE;
      $response->addCommand(new RemoveCommand('#update-field-wrapper'));
      $response->addCommand(new AppendCommand('.form-item--cats-image', '<div class="error" id="update-field-wrapper">Please add the cat`s image</div>'));
    }

    if ($flag === TRUE) {
      // Get file data and save it
      $file_data = $values['cats_image'];
      $file = File::load($file_data[0]);
      $file_id = $file->id();
      $file->setPermanent();
      $file->save();

      // Insert data into the 'helper' table
      \Drupal::database()->insert('helper')->fields([
        'cat_name' => $values['cat_name'],
        'user_email' => $values['user_email'],
        'cats_image_id' => $file_id,
        'created' => time(),
      ])->execute();

      // Display success message
      \Drupal::messenger()->addStatus('Your form has been sent.');

      // Redirect to the specified URL
      $url = Url::fromUri('internal:/helper/cats');
      $redirect_command = new RedirectCommand($url->toString());
      $response->addCommand($redirect_command);

      // Clear form field errors and messages
      $response->addCommand(new HtmlCommand('#name-field-wrapper', ''));
      $response->addCommand(new HtmlCommand('#email-field-wrapper', ''));
      $response->addCommand(new RemoveCommand('#update-field-wrapper'));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No custom validation for the entire form.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No custom form submission logic.
  }
}
