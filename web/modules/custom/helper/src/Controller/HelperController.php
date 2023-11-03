<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelperController extends ControllerBase {
  public function cats_title (){
    $content = [];

    $content['title'] = 'Hello! You can add here a photo of your cat.';
    return [
      '#theme' => 'cats',
      '#content' => $content,
    ];
  }
}
