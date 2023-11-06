<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelperController extends ControllerBase {
  public function ElementsPage (){
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\HelperFormGetNameCat');
    return [
      '#theme' => 'cats',
      '#form' => $form,
    ];
  }
}
