<?php

namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Form controller for the Cat List Control form.
 */
class CatListControlForm extends FormBase {

  protected $session;

  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form-control-cat-list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = [
      'cat_name' => $this->t('Cat Name'),
      'user_email' => $this->t('User Email'),
      'cats_image' => $this->t('Cat Image'),
      'created' => $this->t('Created'),
      'edit' => $this->t('Edit'),
      'delete' => $this->t('Delete'),
    ];

    // Library modal window
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['cats'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#empty' => $this->t('No cats found'),
      '#options' => $this->getCats(),
      '#attributes' => ['id' => 'cat-list-table'],
    ];

    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Selected Cats'),
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  protected function getCats() {
    $query = \Drupal::database();
    $result = $query->select('helper', 'h')
      ->fields('h', [
        'id',
        'cat_name',
        'user_email',
        'cats_image_id',
        'created'
      ])
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    $cats = [];
    foreach ($result as $row) {
      $cats[$row->id] = [
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'cats_image' => $this->buildCatImageMarkup($row->cats_image_id),
        'created' => date('d/m/Y H:i:s', $row->created),
        'edit' => $this->buildEditLink($row->id),
        'delete' => $this->buildDeleteLink($row->id),
      ];
    }

    return $cats;
  }

  /**
   * Builds markup for the cat image.
   */
  protected function buildCatImageMarkup($catsImageId) {
    $file = File::load($catsImageId);
    $image_url = $file ? file_create_url($file->getFileUri()) : '';

    $image_markup = [
      '#theme' => 'image',
      '#uri' => $image_url,
      '#alt' => t('Cat Image'),
      '#attributes' => [
        'class' => ['responsive-image'],
        'id' => 'responsive-image-' . $catsImageId,
      ],
      '#prefix' => '<div class="image-container" id="image-container-' . $catsImageId . '">',
      '#sufix' => '</div>'
    ];
    return render($image_markup);
  }

  /**
   * Builds the edit link for a cat.
   */
  protected function buildEditLink($catId) {
    $url = Url::fromRoute('helper.edit', ['id' => $catId]);
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toRenderable();
    $edit_link['#attributes']['class'][] = 'edit-cat-info';
    $edit_link['#attributes']['class'][] = 'button';
    $edit_link['#attributes']['id'] = 'edit-cat-info-' . $catId;
    return render($edit_link);
  }

  /**
   * Builds the delete link for a cat.
   */
  protected function buildDeleteLink($catId) {
    $delete_link = [
      '#markup' => '<a href="/confirmation-delete/' . $catId . '" class="use-ajax button" data-dialog-type="modal">Delete</a>',
    ];
    return render($delete_link);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Perform bulk deletion based on selected cats.
    $selected_cats = $form_state->getValue('cats');

    // Check if not all elements in $selected_cats are 0.
    if ($selected_cats && count(array_filter($selected_cats, function ($catId) { return $catId != 0; })) > 0) {
      // Store the selected cat IDs in the session.
      $this->session->set('selected_cats', $selected_cats);
      // Redirect to the confirmation bulk delete page.
      $form_state->setRedirect('helper.confirmation-bulk-delete');
    }
    else {
      // Handle the case where not all elements are selected.
      // You may display a message or take other actions as needed.
      \Drupal::messenger()->addError($this->t('Please select the cat you want to delete.'));
    }
  }
}
