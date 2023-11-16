<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ConfirmationBulkDelete extends FormBase {

  public function getFormId() {
    return 'confirmation-bulk-delete-form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
//    $form['title'] = [
//      '#markup' => '<p>' . t("Do you agree to delete '@name' data?", ['@name' => $name]) . '</p>',
//    ];
    $selected_cats = $form_state->get('selected_cats') ?: [];
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

  public function functionCancel(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $response = new AjaxResponse();
    $url = Url::fromUri('internal:/admin/structure/cats-list');
    $redirect_command = new RedirectCommand($url->toString());
    $response->addCommand($redirect_command);
    return $response;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
/*    \Drupal::messenger()->addStatus('<pre>' . print_r($selected_cats, TRUE) . '</pre>');
    \Drupal::messenger()->addStatus(t('User Details Updated Successfully'));*/

  }
}
