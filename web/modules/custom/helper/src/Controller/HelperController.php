<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\file\Entity\File;

class HelperController extends ControllerBase {
  public function ElementsPage (){
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\HelperFormGetCat');
    return [
      '#theme' => 'cats',
      '#form' => $form,
    ];
  }

  public function catList() {
    $current_user = \Drupal::currentUser(); // Get user info

    $is_admin = in_array('administrator', $current_user->getRoles()); // Check is admin

    $query = \Drupal::database(); // Query to DB

    $result = $query->select('helper','h')
      ->fields('h',['id', 'cat_name','user_email','cats_image_id','created'])
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);
    $content = [];
    $bulk['#attached']['library'] = 'core/drupal.dialog.ajax';
    $bulk = [
      '#markup' => '<a href="/confirmation-bulk-delete" class="use-ajax button delete-selected" data-dialog-type="modal">Delete Selected</a>'
    ];
    foreach ($result as $row) {
      $file = File::load($row->cats_image_id);
      $image_url = $file ? file_create_url($file->getFileUri()) : '';
      $id = $row->id;
      $edit_link = Link::createFromRoute('', 'helper.edit', ['id' => $row->id]);
      $delete_link = Link::createFromRoute('', 'helper.form-submit-delete', ['id' => $row->id]);

      $edit = $edit_link->toRenderable();
      $edit['#title'] = $this->t('Edit');
      $edit['#attributes']['class'][] = 'edit-cat-info';
      $edit['#attributes']['class'][] = 'button';
      $edit['#attributes']['id'] = 'edit-cat-info-' . $row->id;

      $build['#attached']['library'] = 'core/drupal.dialog.ajax';
      $checkbox = [
        '#type' => 'checkbox',
        '#attributes' => [
          'class' => ['cat-checkbox'],
        ],
        '#return_value' => $row->id,
        '#name' => 'selected_cats[' . $row->id . ']',
      ];
      $group = [
        '#markup' => \Drupal::service('renderer')->render($checkbox)
      ];
      $build = [
        '#markup' => '<a href="/confirmation-delete/' . $row->id . '" class="use-ajax button" data-dialog-type="modal">Delete</a>'
      ];

      $content[] = [
        'checkbox' => $group,
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'cats_image' => $image_url,
        'id_image' => $row->cats_image_id,
        'created' => date('d/m/Y H:i:s', $row->created),
        'control' => [
          'edit' => $edit,
          'build' => $build
        ],
        'id' => $id,
      ];
    }
    $infoCats = [
      '#theme' => 'cats-view',
      '#content' => $content,
      '#is_admin' => $is_admin,
      '#bulk_deletion' => $bulk
    ];
    return $infoCats;
  }

  public function edit(){
    $form = \Drupal::formBuilder()->getForm('Drupal\helper\Form\FormEditCatInfo');
    return [
      '#theme' => 'cats',
      '#form' => $form,
    ];
  }
}
