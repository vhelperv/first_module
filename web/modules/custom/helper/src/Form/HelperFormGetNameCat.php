<?php

namespace Drupal\helper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;

class HelperFormGetNameCat extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'cats_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['cat-name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('The name must be between 2 and 32 characters long.'),
    );
    $form['user-email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#description' => $this->t('The email address can only contain Latin letters, the underscore character (_), or the hyphen character (-).'),
    );

    $form['actions']['#type'] = 'actions';
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
      '#ajax' => array(
        'callback' => '::validateForm',
      )
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $name = $form_state->getValue('cat-name');
    $response = new AjaxResponse();
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 32) {
      $response->addCommand(new MessageCommand('The name must be between 2 and 32 characters long.',NULL, ['type' => 'error']));
    }
    else {
      $response->addCommand(new MessageCommand('The name is valid.',NULL));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
  }
}
