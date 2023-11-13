<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;

class HelperController extends ControllerBase {
  public function ElementsPage (){
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\HelperFormGetNameCat');
    return [
      '#theme' => 'cats',
      '#form' => $form,
    ];
  }

  public function catList() {
    // Створюємо запит для отримання данних з таблиці helper.
    $query = \Drupal::database();
    $result = $query->select('helper','h')
      ->fields('h',['cat_name','user_email','cats_image_id','created'])
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);
    $content = [];

    foreach ($result  as $row) {
      $file = File::load($row->cats_image_id);
      $image_url = $file ? file_create_url($file->getFileUri()) : '';

      $content[] = [
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'cats_image' => $image_url,
        'created' => date('d-m-Y', $row->created),
      ];
    }

    $infoCats = [
      '#theme' => 'cats-view',
      '#content' => $content
    ];

    return $infoCats;
  }
}
