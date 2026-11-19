<?php

declare(strict_types=1);

namespace Drupal\phoenix_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_inventory\AssetInventoryInterface;
use Drupal\fraction\Fraction;

/**
 * Guided stock movement form backed by farmOS quantities and Input logs.
 */
final class StockMovementForm extends FormBase {

  /**
   * Resolve the inventory service on demand so AJAX form serialization is safe.
   */
  private function inventory(): AssetInventoryInterface {
    return \Drupal::service('asset.inventory');
  }

  public function getFormId(): string {
    return 'phoenix_inventory_stock_movement';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $direction = 'receive'): array {
    if (!in_array($direction, ['receive', 'issue'], TRUE)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $form_state->set('direction', $direction);
    if (!$form_state->get('movement_token')) {
      $form_state->set('movement_token', \Drupal::service('uuid')->generate());
    }
    // Keep a stable submission identifier in the rendered form. Browser Back
    // must resend the same identifier, even if Drupal rebuilds form state.
    $form['movement_token'] = [
      '#type' => 'hidden',
      '#value' => (string) $form_state->get('movement_token'),
    ];
    $storage = \Drupal::entityTypeManager()->getStorage('asset');
    $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'material')->sort('name')->execute();
    $options = [];
    foreach ($storage->loadMultiple($ids) as $asset) {
      if ($asset->access('view')) {
        $options[$asset->id()] = $asset->label() . ' (#' . $asset->id() . ')';
      }
    }
    $selected = $form_state->getValue('asset') ?: ($form_state->getUserInput()['asset'] ?? NULL);
    $selected_asset = $selected && isset($options[$selected]) ? $storage->load($selected) : NULL;
    $pairs = $selected_asset ? $this->stockPairs($selected_asset->id()) : [];
    $single_pair = count($pairs) === 1 ? reset($pairs) : NULL;
    $form['asset'] = [
      '#type' => 'select',
      '#title' => $this->t('Material batch'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select material -'),
      '#required' => TRUE,
      '#ajax' => ['callback' => '::refreshStockForm', 'wrapper' => 'phoenix-stock-details', 'event' => 'change'],
    ];
    $form['stock_details'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'phoenix-stock-details'],
    ];
    $form['stock_details']['measure'] = [
      '#parents' => ['measure'],
      '#type' => 'select',
      '#title' => $this->t('Quantity measure'),
      '#default_value' => $single_pair && $single_pair['measure'] !== '' ? $single_pair['measure'] : NULL,
      '#disabled' => $single_pair !== NULL,
      '#description' => $single_pair && $single_pair['measure'] === '' ? $this->t('Existing stock has no measure recorded. The original measure will be preserved for stock movements.') : NULL,
      '#options' => [
        'count' => $this->t('Count'),
        'weight' => $this->t('Weight'),
        'volume' => $this->t('Volume'),
      ],
      '#required' => TRUE,
    ];
    if ($single_pair && $single_pair['measure'] === '') {
      $form['stock_details']['measure']['#type'] = 'item';
      $form['stock_details']['measure']['#markup'] = $this->t('Not specified (existing inventory record)');
      unset($form['stock_details']['measure']['#options'], $form['stock_details']['measure']['#required']);
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('unit');
    $units = [];
    foreach ($terms as $term) {
      $units[$term->tid] = $term->name;
    }
    $form['stock_details']['units'] = [
      '#parents' => ['units'],
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
        $balances = $this->inventory()->getInventory($selected_asset);
        $balance = array_values(array_filter($balances, static fn (array $entry): bool => (int) ($entry['units'] ?? 0) === (int) $single_pair['units'] && (string) ($entry['measure'] ?? '') === $single_pair['measure']));
        if (!$balance && count($balances) === 1) {
          $balance = array_values($balances);
        }
        $form['stock_details']['available'] = [
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
        $form['stock_details']['available'] = [
          '#type' => 'item',
          '#title' => $this->t('Available stock'),
          '#markup' => $this->t('This batch has multiple stock units. Select the correct measure and units carefully.'),
          '#weight' => -1,
        ];
      }
    }
    $form['stock_details']['amount'] = [
      '#parents' => ['amount'],
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
    return $form['stock_details'];
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
      if ($row->units) {
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
    $balance = $this->inventory()->getInventory($asset, $measure, $units);
    if ($form_state->get('direction') === 'issue') {
      if (!$balance || (float) $balance[0]['value'] < (float) $amount) {
        $form_state->setErrorByName('amount', $this->t('Insufficient stock for the selected measure and units.'));
      }
    }
    elseif ($this->inventory()->getInventory($asset) && !$balance) {
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
      $balance = $this->inventory()->getInventory($asset, $measure, $units);
      if (!$balance || (float) $balance[0]['value'] < (float) $amount) {
        $this->messenger()->addError($this->t('Stock changed. The issue was not saved.'));
        return;
      }
    }

    // Use the identifier that was actually posted by the browser, rather
    // than a newly generated identifier from a rebuilt Drupal form.
    $token = (string) ($form_state->getUserInput()['movement_token'] ?? '');
    if (!preg_match('/^[0-9a-f-]{36}$/i', $token)) {
      $this->messenger()->addError($this->t('Invalid stock movement form. Reload and verify inventory before retrying.'));
      return;
    }
    if ($token === '') {
      $this->messenger()->addError($this->t('The form session expired. Reload the form and check inventory before retrying.'));
      return;
    }
    $key = 'phoenix_inventory.movement.' . $token;
    $lock = \Drupal::lock();
    if (!$lock->acquire($key, 30.0)) {
      $this->messenger()->addWarning($this->t('This stock movement is already being processed. Check inventory before retrying.'));
      return;
    }
    try {
      if (\Drupal::state()->get($key)) {
        $this->messenger()->addWarning($this->t('This form was already submitted successfully. No additional stock movement was recorded.'));
        $form_state->setRedirect('phoenix_inventory.workspace');
        return;
      }
      $this->saveMovement($form_state, $asset, $measure, $units, $amount);
      \Drupal::state()->set($key, TRUE);
    }
    catch (\Throwable $e) {
      \Drupal::logger('phoenix_inventory')->error('Stock movement failed: @message', ['@message' => $e->getMessage()]);
      $this->messenger()->addError($this->t('The stock movement could not be saved. Check inventory before retrying.'));
      return;
    }
    finally {
      $lock->release($key);
    }
    $form_state->setRedirect('phoenix_inventory.workspace');
  }

  /**
   * Persist a movement and its log together.
   */
  private function saveMovement(FormStateInterface $form_state, $asset, string $measure, int $units, string $amount): void {
    $manager = \Drupal::entityTypeManager();
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
    $transaction = \Drupal::database()->startTransaction();
    try {
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
    }
    catch (\Throwable $e) {
      $transaction->rollBack();
      throw $e;
    }
    unset($transaction);
    $this->messenger()->addStatus($this->t('Stock movement recorded as Input log @id.', ['@id' => $log->id()]));
  }

}
