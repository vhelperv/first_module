<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;

class ConfirmationDeleteForm extends FormBase {

  public function getFormId() {
    return 'cats_delete_confirmation';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_url = \Drupal::request()->getRequestUri();
    $form_state->set('current_url', $current_url);

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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_url = $form_state->get('current_url');
    $id = $this->getEntityIdFromUrl($current_url);
    $query = \Drupal::database();
    $query->delete('helper')
      ->condition('id',$id,'=')
      ->execute();
    $url = Url::fromUri('internal:/helper/cats-view');
    $form_state->setRedirectUrl($url);
    drupal_flush_all_caches();
  }
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
  public function functionCancel(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('.ui-dialog'));
    $response->addCommand(new RemoveCommand('.ui-front'));
    return $response;
  }
}
