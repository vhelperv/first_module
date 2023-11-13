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
use Drupal\Core\Render\Markup;

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
    if ($form_state->getValue('reset-upload-field')) {
      $form['upload']['file']['#file'] = false;
      $form['upload']['file']['filename'] = [];
      $form['upload']['file']['#value']['fid'] = '0';
    }
    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('The name must be between 2 and 32 characters long.'),
      '#default_value' => '',
    ];
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
    $form['cats_image'] = [
      '#type' => 'managed_file',
      '#title' => $this ->t('Image Upload'),
      '#required' => TRUE,
      '#description' => $this->t('Add a photo of a cat with the extension jpg, jpeg or png. The maximum size is 2MB'),
      '#default_value' => [],
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
        'event' => 'click'
      ],
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $formField = $form_state->getValues();
    $catName = trim($formField['cat_name']);
    $userEmail = trim($formField['user_email']);

    if (mb_strlen($catName, 'UTF-8') < 2 || mb_strlen($catName, 'UTF-8') > 32) {
      $form_state->setErrorByName('cat_name', $this->t('The cat name must be between 2 and 32 characters long.'));
    }
    if (!preg_match('/^[a-zA-Z\-_@.]+$/', $userEmail)) {
      $form_state->setErrorByName('user_email', $this->t('The email address is invalid.'));
    } elseif (!str_contains($userEmail, '@')) {
      $form_state->setErrorByName('user_email', $this->t('Email must contain @.'));
    } elseif(substr($userEmail, -1) === '@') {
      $form_state->setErrorByName('user_email', $this->t('A domain is required.'));
    }

  }
  public function validateEmail(array &$form, FormStateInterface $form_state) : AjaxResponse {
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
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $values = $form_state->getValues();
    $file_data = $values['cats_image'];
    $file = \Drupal\file\Entity\File::load($file_data[0]);
    $file->setPermanent();
    $file->save();
    $file_id = $file->id();
    $response = new AjaxResponse();
    if (!$form_state->getErrors()) {
      \Drupal::database()->insert('helper')->fields([
        'cat_name' => $values['cat_name'],
        'user_email' => $values['user_email'],
        'cats_image_id' => $file_id,
        'created' => time(),
      ])->execute();

      // Заміна поточного поля файлу новим пустим полем.
/*      $response->addCommand(new ReplaceCommand(
        '.form-managed-file',
        Markup::create('<input data-drupal-selector="edit-cats-image-upload" type="file" id="edit-cats-image-upload" name="files[cats_image]" size="22" class="js-form-file form-file form-element form-element--type-file form-element--api-file" data-once="fileValidate auto-file-upload"><input class="js-hide upload-button button js-form-submit form-submit" data-drupal-selector="edit-cats-image-upload-button" formnovalidate="formnovalidate" type="submit" id="edit-cats-image-upload-button" name="cats_image_upload_button" value="Upload" data-once="drupal-ajax"><input data-drupal-selector="edit-cats-image-fids" type="hidden" name="cats_image[fids]">')
      ));*/
      $response->addCommand(new InvokeCommand('#edit-cat-name', 'val', ['']));
      $response->addCommand(new InvokeCommand('#edit-user-email', 'val', ['']));
      $response->addCommand(new MessageCommand("User Details Submitted Successfully", NULL, ['type' => 'status'], TRUE));
    } else {
      $response->addCommand(new MessageCommand("Form Invalid", NULL, ['type' => 'error'], TRUE));
    }
    return $response;
  }
}
