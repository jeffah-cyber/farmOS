<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\asset\Entity\AssetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates a scheduled farmOS activity for one Plantation Block.
 */
final class PlantationTaskForm extends FormBase {

  /**
   * Constructs the task form.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'phoenix_plantation_task_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AssetInterface $asset = NULL): array {
    if ($asset === NULL || $asset->bundle() !== 'land'
      || !$asset->hasField('land_type')
      || $asset->get('land_type')->value !== 'plantation_block') {
      throw new NotFoundHttpException();
    }
    // Keep the association on the server; it is not an editable request value.
    $form_state->set('block_id', $asset->id());
    $form['block'] = [
      '#type' => 'item',
      '#title' => $this->t('Plantation Block'),
      '#plain_text' => $asset->label(),
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    $form['due_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Due date'),
      '#required' => TRUE,
    ];
    $form['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#plain_text' => $this->t('Pending'),
      '#description' => $this->t('After saving, use Edit task / status to mark the task Done or Abandoned.'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save task'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('phoenix_plantation.workspace', [
        'asset' => $asset->id(),
      ]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $name = trim((string) $form_state->getValue('name'));
    if ($name === '') {
      $form_state->setErrorByName('name', $this->t('Enter a task name.'));
    }
    $value = (string) $form_state->getValue('due_date');
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
      $form_state->setErrorByName('due_date', $this->t('Enter a valid due date.'));
    }
    if ($form_state->hasAnyErrors()) {
      return;
    }

    // Match the existing Phoenix date-only convention: local noon.
    $log = $this->entityTypeManager->getStorage('log')->create([
      'type' => 'activity',
      'name' => $name,
      'asset' => [['target_id' => $form_state->get('block_id')]],
      'timestamp' => $date->setTime(12, 0)->getTimestamp(),
      'status' => 'pending',
    ]);
    foreach ($log->validate() as $violation) {
      $form_state->setErrorByName('', $violation->getMessage());
    }
    $form_state->set('task', $log);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $log = $form_state->get('task');
    $log->save();
    $this->messenger()->addStatus($this->t('Task saved: @name', [
      '@name' => $log->label(),
    ]));
    $form_state->setRedirect('phoenix_plantation.workspace', [
      'asset' => $form_state->get('block_id'),
    ]);
  }

}
