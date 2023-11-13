<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
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
    // Отримуємо інформацію про користувача.
    $current_user = \Drupal::currentUser();

    // Перевіряємо, чи користувач є адміністратором.
    $is_admin = in_array('administrator', $current_user->getRoles());

    // Створюємо запит для отримання даних з таблиці helper.
    $query = \Drupal::database();
    $result = $query->select('helper','h')
      ->fields('h',['id', 'cat_name','user_email','cats_image_id','created'])
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    $content = [];
    foreach ($result as $row) {
      $file = File::load($row->cats_image_id);
      $image_url = $file ? file_create_url($file->getFileUri()) : '';

      $edit_url = Url::fromRoute('helper.edit', ['id' => $row->id]);
      $delete_url = Url::fromRoute('helper.delete', ['id' => $row->id]);

      $edit_link = $is_admin ? Link::fromTextAndUrl(t('Edit'), $edit_url) : '';
      $delete_link = $is_admin ? Link::fromTextAndUrl(t('Delete'), $delete_url) : '';
      $content[] = [
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'cats_image' => $image_url,
        'id_image' => $row->cats_image_id,
        'created' => date('d/m/Y H:i:s',$row->created),
        'edit_button' => $edit_link,
        'delete_button' => $delete_link,
      ];
    }
    $infoCats = [
      '#theme' => 'cats-view',
      '#content' => $content
    ];

    return $infoCats;
  }

  public function edit($id) {

  }

  public function delete($id) {

  }
}
