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
    $storage = \Drupal::entityTypeManager()->getStorage('asset');
    $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'material')->sort('name')->execute();
    $options = [];
    foreach ($storage->loadMultiple($ids) as $asset) {
      if ($asset->access('view')) {
        $options[$asset->id()] = $asset->label() . ' (#' . $asset->id() . ')';
      }
    }
    $selected = $form_state->getValue('asset') ?? $form_state->getUserInput()['asset'] ?? NULL;
    $selected_asset = $selected && isset($options[$selected]) ? $storage->load($selected) : NULL;
    $pairs = $selected_asset ? $this->stockPairs($selected_asset->id()) : [];
    $single_pair = count($pairs) === 1 ? reset($pairs) : NULL;
    $form['#prefix'] = '<div id="phoenix-stock-movement-form">';
    $form['#suffix'] = '</div>';
    $form['asset'] = [
      '#type' => 'select',
      '#title' => $this->t('Material batch'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select material -'),
      '#required' => TRUE,
      '#ajax' => ['callback' => '::refreshStockForm', 'wrapper' => 'phoenix-stock-movement-form', 'event' => 'change'],
    ];
    $form['measure'] = [
      '#type' => 'select',
      '#title' => $this->t('Quantity measure'),
      '#default_value' => $single_pair['measure'] ?? NULL,
      '#disabled' => $single_pair !== NULL,
      '#options' => [
        'count' => $this->t('Count'),
        'weight' => $this->t('Weight'),
        'volume' => $this->t('Volume'),
      ],
      '#required' => TRUE,
    ];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('unit');
    $units = [];
    foreach ($terms as $term) {
      $units[$term->tid] = $term->name;
    }
    $form['units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => $units,
      '#default_value' => $single_pair['units'] ?? NULL,
      '#disabled' => $single_pair !== NULL,
      '#empty_option' => $this->t('- Select unit -'),
      '#required' => TRUE,
      '#description' => $this->t('Use the same measure and unit as the existing balance for this material batch.'),
    ];
    if ($selected_asset) {
      if ($single_pair) {
        $balance = $this->inventory->getInventory($selected_asset, $single_pair['measure'], $single_pair['units']);
        $form['available'] = [
          '#type' => 'item',
          '#title' => $this->t('Available stock'),
          '#markup' => $this->t('@quantity @unit', [
            '@quantity' => $balance[0]['value'] ?? '0',
            '@unit' => $units[$single_pair['units']] ?? '',
          ]),
          '#weight' => -1,
        ];
      }
      elseif (count($pairs) > 1) {
        $form['available'] = [
          '#type' => 'item',
          '#title' => $this->t('Available stock'),
          '#markup' => $this->t('This batch has multiple stock units. Select the correct measure and units carefully.'),
          '#weight' => -1,
        ];
      }
    }
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

  /**
   * Rebuild the form when a material batch is selected.
   */
  public function refreshStockForm(array &$form, FormStateInterface $form_state): array {
    $form_state->setRebuild(TRUE);
    return $form;
  }

  /**
   * Get recorded measure/unit combinations for a material batch.
   */
  private function stockPairs(int|string $asset_id): array {
    $query = \Drupal::database()->select('quantity', 'q');
    $query->fields('q', ['measure', 'units']);
    $query->condition('q.inventory_asset', $asset_id);
    $query->isNotNull('q.inventory_adjustment');
    $query->groupBy('q.measure');
    $query->groupBy('q.units');
    $pairs = [];
    foreach ($query->execute() as $row) {
      if ($row->measure && $row->units) {
        $pairs[] = ['measure' => (string) $row->measure, 'units' => (int) $row->units];
      }
    }
    return $pairs;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $amount = (string) $form_state->getValue('amount');
    if (!preg_match('/^\d+(?:\.\d{1,3})?$/', $amount) || (float) $amount <= 0) {
      $form_state->setErrorByName('amount', $this->t('Enter a positive quantity with at most three decimal places.'));
      return;
    }
    $asset = \Drupal::entityTypeManager()->getStorage('asset')->load($form_state->getValue('asset'));
    if (!$asset || $asset->bundle() !== 'material' || !$asset->access('view')) {
      $form_state->setErrorByName('asset', $this->t('Select an accessible material batch.'));
      return;
    }
    $pairs = $this->stockPairs($asset->id());
    if (count($pairs) === 1) {
      $pair = reset($pairs);
      $measure = $pair['measure'];
      $units = $pair['units'];
    }
    else {
      $measure = (string) $form_state->getValue('measure');
      $units = (int) $form_state->getValue('units');
    }
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
    $manager = \Drupal::entityTypeManager();
    $asset = $manager->getStorage('asset')->load($form_state->getValue('asset'));
    $pairs = $this->stockPairs($asset->id());
    if (count($pairs) === 1) {
      $pair = reset($pairs);
      $measure = $pair['measure'];
      $units = $pair['units'];
    }
    else {
      $measure = (string) $form_state->getValue('measure');
      $units = (int) $form_state->getValue('units');
    }
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
