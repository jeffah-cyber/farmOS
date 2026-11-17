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
          ['#type' => 'link', '#title' => $this->t('Register material batch'), '#url' => Url::fromRoute('entity.asset.add_form', ['asset_type' => 'material'])],
        ],
      ],
      'stock' => [
        '#type' => 'table',
        '#header' => [$this->t('Material batch'), $this->t('Lot number'), $this->t('Current stock')],
        '#rows' => $rows,
        '#empty' => $this->t('No material batches available.'),
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
