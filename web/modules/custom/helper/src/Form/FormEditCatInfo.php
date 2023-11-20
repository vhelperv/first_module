<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\RedirectCommand;

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
      '#default_value' => '',
    ];

    // User email field with Ajax validation
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
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

  // Validate form method
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  // Validate email method with Ajax response
  public function validateEmail(array &$form, FormStateInterface $form_state) : AjaxResponse {
    return parent::validateEmail($form, $form_state);
  }

  // Submit method for updating cat information
  public function submitUpdate(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $values = $form_state->getValues();
    $file_data = $values['cats_image'];
    $file = \Drupal\file\Entity\File::load($file_data[0]);
    $file->setPermanent();
    $file->save();
    $file_id = $file->id();

    // Get cat ID from route parameters
    $current_path = \Drupal::service('path.current')->getPath();
    $route_match = \Drupal::routeMatch();
    $route_parameters = $route_match->getParameters();
    $id = $route_parameters->get('id');
    $response = new AjaxResponse();

    // Check for form errors
    if (!$form_state->getErrors()) {
      // Update database with new cat information
      \Drupal::database()->update('helper')
        ->fields([
          'cat_name' => $values['cat_name'],
          'user_email' => $values['user_email'],
          'cats_image_id' => $file_id,
        ])
        ->condition('id', $id,'=')
        ->execute();

      // Clear caches
      drupal_flush_all_caches();

      // Redirect to the cats list page
      $url = Url::fromUri('internal:/admin/structure/cats-list');
      $redirect_command = new RedirectCommand($url->toString());
      $response->addCommand($redirect_command);

      // Display success message
      \Drupal::messenger()->addStatus(t('User Details Updated Successfully'));
    } else {
      // Display error message if form has errors
      $response->addCommand(new MessageCommand("The entered data is not valid. Update declined", NULL, ['type' => 'error'], TRUE));
    }
    return $response;
  }
}
