<?php

declare(strict_types=1);

namespace Drupal\phoenix_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_inventory\AssetInventoryInterface;
use Drupal\fraction\Fraction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Guided stock movement form backed by farmOS quantities and Input logs.
 */
final class StockMovementForm extends FormBase {

  public function __construct(
    private readonly AssetInventoryInterface $inventory,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get(AssetInventoryInterface::class));
  }

  public function getFormId(): string {
    return 'phoenix_inventory_stock_movement';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $direction = 'receive'): array {
    if (!in_array($direction, ['receive', 'issue'], TRUE)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $form_state->set('direction', $direction);
    $storage = $this->entityTypeManager()->getStorage('asset');
    $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'material')->sort('name')->execute();
    $options = [];
    foreach ($storage->loadMultiple($ids) as $asset) {
      if ($asset->access('view')) {
        $options[$asset->id()] = $asset->label() . ' (#' . $asset->id() . ')';
      }
    }
    $form['asset'] = [
      '#type' => 'select',
      '#title' => $this->t('Material batch'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select material -'),
      '#required' => TRUE,
    ];
    $form['measure'] = [
      '#type' => 'select',
      '#title' => $this->t('Quantity measure'),
      '#options' => [
        'count' => $this->t('Count'),
        'weight' => $this->t('Weight'),
        'volume' => $this->t('Volume'),
      ],
      '#required' => TRUE,
    ];
    $terms = $this->entityTypeManager()->getStorage('taxonomy_term')->loadTree('unit');
    $units = [];
    foreach ($terms as $term) {
      $units[$term->tid] = $term->name;
    }
    $form['units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => $units,
      '#empty_option' => $this->t('- Select unit -'),
      '#required' => TRUE,
      '#description' => $this->t('Use the same measure and unit as the existing balance for this material batch.'),
    ];
    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#min' => 0.001,
      '#step' => 0.001,
      '#required' => TRUE,
    ];
    $form['source'] = [
      '#type' => 'textfield',
      '#title' => $direction === 'receive' ? $this->t('Supplier or source') : $this->t('Issued to / purpose'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $direction === 'receive' ? $this->t('Record receipt') : $this->t('Record issue'),
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $amount = (string) $form_state->getValue('amount');
    if (!preg_match('/^\d+(?:\.\d{1,3})?$/', $amount) || (float) $amount <= 0) {
      $form_state->setErrorByName('amount', $this->t('Enter a positive quantity with at most three decimal places.'));
      return;
    }
    $asset = $this->entityTypeManager()->getStorage('asset')->load($form_state->getValue('asset'));
    if (!$asset || $asset->bundle() !== 'material' || !$asset->access('view')) {
      $form_state->setErrorByName('asset', $this->t('Select an accessible material batch.'));
      return;
    }
    $measure = (string) $form_state->getValue('measure');
    $units = (int) $form_state->getValue('units');
    $balance = $this->inventory->getInventory($asset, $measure, $units);
    if ($form_state->get('direction') === 'issue') {
      if (!$balance || (float) $balance[0]['value'] < (float) $amount) {
        $form_state->setErrorByName('amount', $this->t('Insufficient stock for the selected measure and units.'));
      }
    }
    elseif ($this->inventory->getInventory($asset) && !$balance) {
      $form_state->setErrorByName('units', $this->t('This batch already has stock recorded in different units or a different measure. Use the existing combination.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $manager = $this->entityTypeManager();
    $asset = $manager->getStorage('asset')->load($form_state->getValue('asset'));
    $measure = (string) $form_state->getValue('measure');
    $units = (int) $form_state->getValue('units');
    $amount = (string) $form_state->getValue('amount');

    // Recheck at submission. This is not a transactional concurrency lock.
    if ($form_state->get('direction') === 'issue') {
      $balance = $this->inventory->getInventory($asset, $measure, $units);
      if (!$balance || (float) $balance[0]['value'] < (float) $amount) {
        $this->messenger()->addError($this->t('Stock changed. The issue was not saved.'));
        return;
      }
    }

    $fraction = Fraction::createFromDecimal($amount);
    $quantity = $manager->getStorage('quantity')->create([
      'type' => 'material',
      'measure' => $measure,
      'value' => [
        'numerator' => $fraction->getNumerator(),
        'denominator' => $fraction->getDenominator(),
      ],
      'units' => ['target_id' => $units],
      'inventory_asset' => ['target_id' => $asset->id()],
      'inventory_adjustment' => $form_state->get('direction') === 'receive' ? 'increment' : 'decrement',
    ]);
    $quantity->save();

    $log = $manager->getStorage('log')->create([
      'type' => 'input',
      'name' => ($form_state->get('direction') === 'receive' ? 'Stock receipt: ' : 'Stock issue: ') . $asset->label(),
      'timestamp' => \Drupal::time()->getRequestTime(),
      'status' => 'done',
      'source' => (string) $form_state->getValue('source'),
      'quantity' => [
        'target_id' => $quantity->id(),
        'target_revision_id' => $quantity->getRevisionId(),
      ],
    ]);
    $log->save();
    $this->messenger()->addStatus($this->t('Stock movement recorded as Input log @id.', ['@id' => $log->id()]));
    $form_state->setRedirect('phoenix_inventory.workspace');
  }

}
