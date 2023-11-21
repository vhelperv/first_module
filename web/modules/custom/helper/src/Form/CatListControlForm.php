<?php

namespace Drupal\helper\Form;

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
    // Check if the current user has admin permission
    $isAdmin = \Drupal::currentUser()->hasPermission('administer site configuration');
    // Get the current route name
    $current_route = \Drupal::routeMatch()->getRouteName();
    // Check if the current route is 'helper.cats_list'
    $isCatsListRoute = $current_route == 'helper.cats_list';

    // Table header for cat list
    $header = [
      'cat_name' => $this->t('Cat Name'),
      'user_email' => $this->t('User Email'),
      'cats_image' => $this->t('Cat Image'),
      'created' => $this->t('Created'),
    ];

    // Attach library for modal window
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Check if the user is not on the 'helper.cats_list' route and is an admin
    if (!$isCatsListRoute && $isAdmin) {
      // Table element for admin
      $form['cats_user'] = [
        '#type' => 'table',
        '#header' => $header + ['edit' => $this->t('Edit'), 'delete' => $this->t('Delete')],
        '#empty' => $this->t('No cats found'),
        '#rows' => $this->getCats(),
        '#attributes' => ['id' => 'cat-list-table-user'],
      ];

      return $form;
    }

    // Display for regular user
    if (!$isAdmin) {
      // Table element for regular user
      $form['cats_user'] = [
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('No cats found'),
        '#rows' => $this->getCatsAsRows(),
        '#attributes' => ['id' => 'cat-list-table-user'],
      ];

      return $form;
    }

    // Tableselect element for admin
    $form['cats_admin'] = [
      '#type' => 'tableselect',
      '#header' => $header + ['edit' => $this->t('Edit'), 'delete' => $this->t('Delete')],
      '#empty' => $this->t('No cats found'),
      '#options' => $this->getCats(),
      '#attributes' => ['id' => 'cat-list-table-admin'],
    ];

    // Submit button for bulk delete (visible only for admin)
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Selected Cats'),
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * Helper function to get cat data as rows for the table.
   */
  protected function getCatsAsRows() {
    $cats = $this->getCats();
    $rows = [];

    foreach ($cats as $cat) {
      $rows[] = [
        'cat_name' => $cat['cat_name'],
        'user_email' => $cat['user_email'],
        'cats_image' => $cat['cats_image'],
        'created' => $cat['created'],
      ];
    }

    return $rows;
  }

  // Fetch cat data from the database
  protected function getCats() {
    // Query the database for cat information
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
      // Build an array with cat information
      $isAdmin = \Drupal::currentUser()->hasPermission('administer site configuration');

      $cats[$row->id] = [
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'cats_image' => $this->buildCatImageMarkup($row->cats_image_id),
        'created' => date('d/m/Y H:i:s', $row->created),
        'edit' => $isAdmin ? $this->buildEditLink($row->id) : '',
        'delete' => $isAdmin ? $this->buildDeleteLink($row->id) : '',
      ];
    }

    return $cats;
  }

  // Build markup for displaying cat images
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

  // Build link for editing cat information
  protected function buildEditLink($catId) {
    $url = Url::fromRoute('helper.edit', ['id' => $catId]);
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toRenderable();
    $edit_link['#attributes']['class'][] = 'edit-cat-info';
    $edit_link['#attributes']['class'][] = 'button';
    $edit_link['#attributes']['id'] = 'edit-cat-info-' . $catId;
    return render($edit_link);
  }

  // Build link for deleting cat information
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
    $selected_cats = $form_state->getValue('cats_admin');

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
