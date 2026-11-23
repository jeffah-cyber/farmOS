<?php

declare(strict_types=1);

namespace Drupal\phoenix_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\farm_inventory\AssetInventoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stock overview using farmOS's canonical inventory calculations.
 */
final class InventoryController extends ControllerBase {

  public function __construct(
    private readonly AssetInventoryInterface $inventory,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get(AssetInventoryInterface::class));
  }

  /**
   * Displays material batches and their native farmOS balances.
   */
  public function workspace(): array {
    $storage = $this->entityTypeManager()->getStorage('asset');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'material')
      ->sort('id', 'DESC')
      ->range(0, 100)
      ->execute();

    $rows = [];
    foreach ($storage->loadMultiple($ids) as $asset) {
      if (!$asset->access('view')) {
        continue;
      }

      $balances = [];
      foreach ($this->inventory->getInventory($asset) as $balance) {
        $balances[] = trim($balance['value'] . ' ' . $balance['units']);
      }

      $lot = $asset->hasField('field_phoenix_lot_number') && !$asset->get('field_phoenix_lot_number')->isEmpty()
        ? $asset->get('field_phoenix_lot_number')->value
        : $this->t('Not recorded');

      $rows[] = [
        'data' => [
          ['data' => ['#type' => 'link', '#title' => $asset->label(), '#url' => $asset->toUrl()]],
          ['data' => ['#plain_text' => (string) $lot]],
          ['data' => ['#plain_text' => $balances ? implode(', ', $balances) : (string) $this->t('No stock movements')]],
        ],
      ];
    }

    // Read completed Input logs and their referenced native inventory quantities.
    // Keep the history read-only and respect entity view permissions.
    $history_rows = [];
    $log_storage = $this->entityTypeManager()->getStorage('log');
    $log_ids = $log_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'input')
      ->sort('id', 'DESC')
      ->range(0, 200)
      ->execute();

    $asset_storage = $this->entityTypeManager()->getStorage('asset');
    foreach ($log_storage->loadMultiple($log_ids) as $log) {
      if (!$log->access('view') || !$log->hasField('quantity') || (string) ($log->get('status')->value ?? '') !== 'done') {
        continue;
      }
      foreach ($log->get('quantity')->referencedEntities() as $quantity) {
        if (!$quantity->hasField('inventory_asset') || $quantity->get('inventory_asset')->isEmpty()
          || !$quantity->hasField('inventory_adjustment') || $quantity->get('inventory_adjustment')->isEmpty()) {
          continue;
        }
        $asset_id = $quantity->get('inventory_asset')->target_id;
        $asset = $asset_storage->load($asset_id);
        if (!$asset || !$asset->access('view')) {
          continue;
        }
        $adjustment = (string) $quantity->get('inventory_adjustment')->value;
        $is_correction = str_starts_with((string) ($log->get('source')->value ?? ''), 'CORRECTION of Input log #');
        $movement = match ($adjustment) {
          'increment' => (string) $this->t($is_correction ? 'Correction (+)' : 'Receipt'),
          'decrement' => (string) $this->t($is_correction ? 'Correction (-)' : 'Issue'),
          'reset' => (string) $this->t('Stock reset'),
          default => $adjustment,
        };
        // farmOS stores fractional quantities as numerator / denominator.
        $fraction = $quantity->get('value')->first();
        $parts = $fraction ? $fraction->getValue() : [];
        $numerator = $parts['numerator'] ?? NULL;
        $denominator = $parts['denominator'] ?? NULL;
        $value = ($numerator !== NULL && is_numeric($denominator) && (float) $denominator != 0.0)
          ? rtrim(rtrim(number_format((float) $numerator / (float) $denominator, 3, '.', ''), '0'), '.')
          : (string) $this->t('Unknown quantity');
        $unit = '';
        if ($quantity->hasField('units') && !$quantity->get('units')->isEmpty() && $quantity->get('units')->entity) {
          $unit = $quantity->get('units')->entity->label();
        }
        $timestamp = $log->get('timestamp')->value ?? NULL;
        $date = $timestamp ? \Drupal::service('date.formatter')->format((int) $timestamp, 'short') : (string) $this->t('Not recorded');
        $owner = $log->getOwnerId() ? $this->entityTypeManager()->getStorage('user')->load($log->getOwnerId()) : NULL;
        $staff = $owner ? $owner->getDisplayName() : (string) $this->t('Not recorded');
        $source = $log->hasField('source') && !$log->get('source')->isEmpty()
          ? (string) $log->get('source')->value : '';
        $history_rows[] = [
          'data' => [
            ['data' => ['#plain_text' => $date]],
            ['data' => ['#plain_text' => $asset->label() . ' (#' . $asset->id() . ')']],
            ['data' => ['#plain_text' => $movement]],
            ['data' => ['#plain_text' => trim($value . ' ' . $unit)]],
            ['data' => ['#plain_text' => $staff]],
            ['data' => ['#plain_text' => $source]],
            ['data' => ['#type' => 'link', '#title' => '#' . $log->id(), '#url' => $log->toUrl()]],
          ],
        ];
        if (count($history_rows) >= 100) {
          break 2;
        }
      }
    }

    return [
      'intro' => [
        '#type' => 'container',
        'heading' => ['#markup' => '<h2>' . $this->t('Stock receipts and issues') . '</h2>'],
        'description' => [
          '#markup' => '<p>' . $this->t('Stock balances below come directly from completed farmOS inventory adjustment logs. Record receipts as Increment and issues as Decrement using the native Input log form. Use the same units for each batch; never use Reset for routine transactions.') . '</p>',
        ],
      ],
      'actions' => [
        '#theme' => 'item_list',
        '#items' => [
          ['#type' => 'link', '#title' => $this->t('Receive stock'), '#url' => Url::fromRoute('phoenix_inventory.receive')],
          ['#type' => 'link', '#title' => $this->t('Issue stock'), '#url' => Url::fromRoute('phoenix_inventory.issue')],
          ['#type' => 'link', '#title' => $this->t('Correct stock'), '#url' => Url::fromRoute('phoenix_inventory.adjust')],
          ['#type' => 'link', '#title' => $this->t('Register material batch'), '#url' => Url::fromRoute('entity.asset.add_form', ['asset_type' => 'material'])],
        ],
      ],
      'stock' => [
        '#type' => 'table',
        '#header' => [$this->t('Material batch'), $this->t('Lot number'), $this->t('Current stock')],
        '#rows' => $rows,
        '#empty' => $this->t('No material batches available.'),
      ],
      'history_heading' => [
        '#markup' => '<h2>' . $this->t('Recent stock movement history') . '</h2>',
      ],
      'history' => [
        '#type' => 'table',
        '#header' => [$this->t('Date'), $this->t('Material batch'), $this->t('Movement'), $this->t('Quantity'), $this->t('Recorded by'), $this->t('Source / purpose'), $this->t('Input log')],
        '#rows' => $history_rows,
        '#empty' => $this->t('No accessible completed inventory movements found in recent Input logs.'),
      ],
      'history_note' => [
        '#markup' => '<p>' . $this->t('Showing up to 100 movements from the 200 most recent Input logs. Only records you can view are included.') . '</p>',
      ],
      'limit_note' => [
        '#markup' => '<p>' . $this->t('Showing up to 100 most recently created material batches.') . '</p>',
      ],
      '#cache' => [
        'tags' => ['asset_list', 'log_list', 'quantity_list'],
        'contexts' => ['user.permissions'],
        'max-age' => 0,
      ],
    ];
  }

}
