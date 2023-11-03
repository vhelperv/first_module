<?php

namespace Drupal\helper\Controller;

class HelperController {
  public function message() {
    return [
      '#markup' => 'Hello! You can add here a photo of your cat.'
    ];
  }
}
