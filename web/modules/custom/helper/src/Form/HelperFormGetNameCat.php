<?php

namespace Drupal\helper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['cat-name'] = array(
      '#type' => 'textfield',
      '#title' => t('Your cat’s name:'),
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add cat'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
  }
}
