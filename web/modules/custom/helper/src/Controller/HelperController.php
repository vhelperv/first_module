<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelperController extends ControllerBase {

  // Function to call the form to add a new cat entity
  public function getCatInfo (){
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\HelperFormGetCat');
    return [
      '#theme' => 'cats',
      '#form' => $form,
    ];
  }

  // Function to call a table that displays all cat records from the DB table 'helper'
  public function catList() {
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\CatListControlForm');
    return [
      '#theme' => 'cats-view',
      '#form' => $form,
    ];
  }

  // Function to call the edit cat form
  public function edit(){
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\FormEditCatInfo');
    return [
      '#theme' => 'cats',
      '#form' => $form,
    ];
  }
}
