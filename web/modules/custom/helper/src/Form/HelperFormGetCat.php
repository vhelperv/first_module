<?php
namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\InvokeCommand;

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
  /*FormAjaxResponseBuilder*/
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Reset upload field if specified
    if ($form_state->getValue('reset-upload-field')) {
      $form['upload']['file']['#file'] = false;
      $form['upload']['file']['filename'] = [];
      $form['upload']['file']['#value']['fid'] = '0';
    }

    // Cat name textfield
    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('The name must be between 2 and 32 characters long.'),
      '#default_value' => '',
    ];

    // User email field with Ajax validation
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#description' => $this->t('The email address can only contain Latin letters, the underscore character (_), or the hyphen character (-).'),
      '#default_value' => '',
      '#prefix' => '<div id="email-field-wrapper">',
      '#suffix' => '</div>',
      '#element_validate' => [[$this, 'validateForm']],
      '#ajax' => [
        'callback' => '::validateEmail',
        'event' => 'input'
      ],
    ];

    // Cats image managed file field
    $form['cats_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image Upload'),
      '#required' => TRUE,
      '#description' => $this->t('Add a photo of a cat with the extension jpg, jpeg, or png. The maximum size is 2MB'),
      '#default_value' => [],
      '#upload_location' => 'public://cats',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg', 'jpeg', 'png'],
        'file_validate_size' => [2 * 1024 * 1024],
      ],
      '#prefix' => '<div id="cats-image-wrapper">',
      '#suffix' => '</div>',
    ];

    // Form actions
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Add cat'),
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click'
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate cat name and user email
    $formField = $form_state->getValues();
    $catName = trim($formField['cat_name']);
    $userEmail = trim($formField['user_email']);

    // Check if cat name is within the specified length range
    if (mb_strlen($catName, 'UTF-8') < 2 || mb_strlen($catName, 'UTF-8') > 32) {
      $form_state->setErrorByName('cat_name', $this->t('The cat name must be between 2 and 32 characters long.'));
    }

    // Validate user email format
    if (!preg_match('/^[a-zA-Z\-_@.]+$/', $userEmail)) {
      $form_state->setErrorByName('user_email', $this->t('The email address is invalid.'));
    } elseif (!str_contains($userEmail, '@')) {
      $form_state->setErrorByName('user_email', $this->t('Email must contain @.'));
    } elseif(substr($userEmail, -1) === '@') {
      $form_state->setErrorByName('user_email', $this->t('A domain is required.'));
    }
  }

  // Ajax callback for email field validation
  public function validateEmail(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $emailAddress = $form_state->getValue('user_email');
    $errorMessageInvalid = '<span id="email-error-message" style="color: red; font-size: 15px;">The email address is invalid.</span>';
    $errorMessageMustContain = '<span id="email-error-message" style="color: red; font-size: 15px;">Email must contain @.</span>';
    $errorMessageMissingDomain = '<span id="email-error-message" style="color: red; font-size: 15px;">A domain is required. For example: @gmail.com</span>';

    // Validate the email address format using a regular expression
    if (!preg_match('/^[a-zA-Z\-_@.]+$/', $emailAddress)) {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'addClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
      $response->addCommand(new AppendCommand('#email-field-wrapper .form-type--email', $errorMessageInvalid));
    } elseif (!str_contains($emailAddress, '@')) {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'addClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
      $response->addCommand(new AppendCommand('#email-field-wrapper .form-type--email', $errorMessageMustContain));
    } elseif (substr($emailAddress, -1) === '@') {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'addClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
      $response->addCommand(new AppendCommand('#email-field-wrapper .form-type--email', $errorMessageMissingDomain));
    } else {
      $response->addCommand(new InvokeCommand('#edit-user-email', 'removeClass', ['error']));
      $response->addCommand(new RemoveCommand('#email-error-message'));
    }

    return $response;
  }

  // Reset form values method
  protected function resetFormValues(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $form_state->setValues([]);
    $form_state->setStorage([]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    // Process form submission
    $values = $form_state->getValues();
    $file_data = $values['cats_image'];
    $file = \Drupal\file\Entity\File::load($file_data[0]);
    $file->setPermanent();
    $file->save();
    $file_id = $file->id();
    $response = new AjaxResponse();

    // Check for form errors
    if (!$form_state->getErrors()) {
      // Insert data into the 'helper' table
      \Drupal::database()->insert('helper')->fields([
        'cat_name' => $values['cat_name'],
        'user_email' => $values['user_email'],
        'cats_image_id' => $file_id,
        'created' => time(),
      ])->execute();

      // Clear form values after successful submission
      $response->addCommand(new InvokeCommand('#edit-cat-name', 'val', ['']));
      $response->addCommand(new InvokeCommand('#edit-user-email', 'val', ['']));

      // Optionally clear cache (uncomment if needed)
      // drupal_flush_all_caches();
    } else {
      // Add error message commands if form has errors
      $response->addCommand(new MessageCommand("Form Invalid", NULL, ['type' => 'error'], TRUE));

      if (mb_strlen($values['cat_name'], 'UTF-8') < 2 || mb_strlen($values['cat_name'], 'UTF-8') > 32) {
        $response->addCommand(new MessageCommand("The cat name must be between 2 and 32 characters long.", NULL, ['type' => 'error'], TRUE));
      }
    }

    // Clear form errors
    $form_state->clearErrors();

    return $response;
  }
}
