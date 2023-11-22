<?php
namespace Drupal\helper\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ConfirmationBulkDelete extends FormBase {
  protected $session;

  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session')
    );
  }

  public function getFormId() {
    return 'confirmation-bulk-delete-form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the selected cat IDs from the session.
    $selected_cats = $this->session->get('selected_cats', []);

    // If there are selected cats, display their names in a bulleted list.
    if (!empty($selected_cats)) {
      // Fetch cat names from the database based on the selected cat IDs.
      $query = \Drupal::database();
      $cat_names = $query->select('helper', 'h')
        ->fields('h', ['cat_name'])
        ->condition('id', $selected_cats, 'IN')
        ->execute()
        ->fetchCol();

      // Display the cat names in a bulleted list.
      $form['selected_cats'] = [
        '#type' => 'item',
        '#title' => $this->t('Delete Cats'),
        '#markup' => '<ul><li>' . implode('</li><li>', $cat_names) . '</li></ul>',
      ];
    }

    // Form actions for submit and cancel
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete')
    ];
    $form['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::functionCancel',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the selected cat IDs from the session.
    $selected_cats = $this->session->get('selected_cats', []);

    // Delete entries from the database based on selected cat IDs.
    if (!empty($selected_cats)) {
      $query = \Drupal::database();
      $query->delete('helper')
        ->condition('id', $selected_cats, 'IN')
        ->execute();

      // Clear the session after deletion.
      $this->session->set('selected_cats', []);
    }

    // Redirect to the cats list page.
    $url = Url::fromUri('internal:/admin/structure/cats-list');
    $form_state->setRedirectUrl($url);
  }

  // Ajax callback to cancel and redirect to the cats list page
  public function functionCancel(array &$form, FormStateInterface $form_state) : AjaxResponse {
    $response = new AjaxResponse();
    $url = Url::fromUri('internal:/admin/structure/cats-list');
    $redirect_command = new RedirectCommand($url->toString());
    $response->addCommand($redirect_command);
    return $response;
  }
}
