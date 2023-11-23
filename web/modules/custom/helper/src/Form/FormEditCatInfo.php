<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\file\Entity\File;

class FormEditCatInfo extends HelperFormGetCat {

  public function getFormId(): string {
    return 'cats-edit-form';
  }

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
      '#title' => $this ->t('Image Upload'),
      '#description' => $this->t('Add a photo of a cat with the extension jpg, jpeg or png. The maximum size is 2MB'),
      '#default_value' => [],
      '#upload_location' => 'public://cats',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
        'file_validate_size' => [2 * 1024 * 1024],
      ],
    ];

    // Form actions for submit
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::submitUpdate',
        'event' => 'click'
      ],
    ];

    // Fetch the cat ID from the route parameters
    $route_match = \Drupal::routeMatch();
    $route_parameters = $route_match->getParameters();
    $id = $route_parameters->get('id');

    // Check if $id is available
    if (!empty($id)) {
      // Query the database to get values based on the $id
      $result = \Drupal::database()->select('helper', 'h')
        ->fields('h')
        ->condition('id', $id)
        ->execute()
        ->fetchAssoc();

      // Set default values for form elements
      if ($result) {
        $form['cat_name']['#default_value'] = $result['cat_name'];
        $form['user_email']['#default_value'] = $result['user_email'];

        // If there is a cats_image_id, load the file entity and set it as the default value
        if (!empty($result['cats_image_id'])) {
          $file = \Drupal\file\Entity\File::load($result['cats_image_id']);
          if ($file) {
            $form['cats_image']['#default_value'] = [$file->id()];
          }
        }
      }
    }

    return $form;
  }
  public function validateName(array &$form, FormStateInterface $form_state) : AjaxResponse {
    return parent::validateName($form, $form_state);
  }
  // Validate form method
  public function validateForm(array &$form, FormStateInterface $form_state) {  }

  // Validate email method with Ajax response
  public function validateEmail(array &$form, FormStateInterface $form_state) : AjaxResponse {
    return parent::validateEmail($form, $form_state);
  }

  // Submit method for updating cat information
  public function submitUpdate(array &$form, FormStateInterface $form_state) : AjaxResponse {
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

      // Get cat ID from route parameters
      $route_match = \Drupal::routeMatch();
      $route_parameters = $route_match->getParameters();
      $id = $route_parameters->get('id');
      $response = new AjaxResponse();
      // Update database with new cat information
      \Drupal::database()->update('helper')
        ->fields([
          'cat_name' => $values['cat_name'],
          'user_email' => $values['user_email'],
          'cats_image_id' => $file_id,
        ])
        ->condition('id', $id,'=')
        ->execute();

      // Redirect to the cats list page
      $url = Url::fromUri('internal:/admin/structure/cats-list');
      $redirect_command = new RedirectCommand($url->toString());
      $response->addCommand($redirect_command);

      // Display success message
      \Drupal::messenger()->addStatus('User Details Updated Successfully');

      // Clear form field errors and messages
      $response->addCommand(new HtmlCommand('#name-field-wrapper', ''));
      $response->addCommand(new HtmlCommand('#email-field-wrapper', ''));
      $response->addCommand(new RemoveCommand('#update-field-wrapper'));
    }

    return $response;
  }
}
