<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;

/**
 * Form controller for the Cat Deletion Confirmation form.
 */
class ConfirmationDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cats_delete_confirmation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the current URL and store it in the form state for later use.
    $current_url = \Drupal::request()->getRequestUri();
    $form_state->set('current_url', $current_url);

    // Extract the entity ID from the URL.
    $id = $this->getEntityIdFromUrl($current_url);

    // Query the database to get the cat name associated with the entity ID.
    $query = \Drupal::database();
    $result = $query->select('helper', 'h')
      ->fields('h', ['cat_name'])
      ->condition('id', $id, '=')
      ->execute();
    $name = $result->fetchField();

    // Display a confirmation message.
    $form['title'] = [
      '#markup' => '<p>' . t("Do you agree to delete '@name' data?", ['@name' => $name]) . '</p>',
    ];

    // Form actions for submit and cancel.
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete')
    ];
    $form['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::functionCancel',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the current URL from the form state.
    $current_url = $form_state->get('current_url');

    // Extract the entity ID from the URL.
    $id = $this->getEntityIdFromUrl($current_url);

    // Delete the record from the database based on the entity ID.
    $query = \Drupal::database();
    $query->delete('helper')
      ->condition('id', $id, '=')
      ->execute();

    // Redirect to the cats list page after deletion.
    $url = Url::fromUri('internal:/admin/structure/cats-list');
    $form_state->setRedirectUrl($url);

    // Flush all Drupal caches after deletion.
    drupal_flush_all_caches();
  }

  /**
   * Helper function to extract the entity ID from the URL.
   */
  private function getEntityIdFromUrl($url) {
    // Parse the URL.
    $url_parts = parse_url($url);

    // Extract the path.
    $path = isset($url_parts['path']) ? $url_parts['path'] : '';

    // Use regular expressions or other methods to extract the ID from the path.
    $matches = [];
    if (preg_match('/\/confirmation-delete\/(\d+)/', $path, $matches)) {
      return $matches[1];
    }

    return null;
  }

  /**
   * Ajax callback function to cancel the form and close the modal.
   */
  public function functionCancel(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $response = new AjaxResponse();

    // Remove the confirmation dialog and overlay.
    $response->addCommand(new RemoveCommand('.ui-dialog'));
    $response->addCommand(new RemoveCommand('.ui-front'));

    return $response;
  }
}
