<?php

declare(strict_types=1);

namespace Drupal\phoenix_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Entry point for farmOS-backed Phoenix stock workflows.
 *
 * This controller intentionally does not maintain a second stock balance.
 * Inventory adjustments remain native farmOS Input logs and quantities.
 */
final class InventoryController extends ControllerBase {

  /**
   * Builds the stock movement workspace.
   */
  public function workspace(): array {
    $links = [
      [
        'title' => $this->t('Receive stock'),
        'description' => $this->t('Create a completed Input log for the material batch, add a Quantity, and set Inventory adjustment to Increment.'),
        'url' => Url::fromRoute('entity.log.add_form', ['log_type' => 'input']),
      ],
      [
        'title' => $this->t('Issue or consume stock'),
        'description' => $this->t('Create a completed Input log for the material batch, add a Quantity, and set Inventory adjustment to Decrement.'),
        'url' => Url::fromRoute('entity.log.add_form', ['log_type' => 'input']),
      ],
      [
        'title' => $this->t('Register a material batch'),
        'description' => $this->t('Create a Material asset with its lot number, manufacture date, and expiry date before receiving stock.'),
        'url' => Url::fromRoute('entity.asset.add_form', ['asset_type' => 'material']),
      ],
    ];

    $items = [];
    foreach ($links as $item) {
      $items[] = [
        '#type' => 'container',
        'link' => [
          '#type' => 'link',
          '#title' => $item['title'],
          '#url' => $item['url'],
        ],
        'description' => [
          '#markup' => '<p>' . $item['description'] . '</p>',
        ],
      ];
    }

    return [
      'intro' => [
        '#type' => 'container',
        'heading' => ['#markup' => '<h2>' . $this->t('Stock receipts and issues') . '</h2>'],
        'description' => [
          '#markup' => '<p>' . $this->t('Phoenix OS records stock changes through the native farmOS inventory engine. Select the correct material batch and unit, record a completed Input log, and verify the updated balance on the material asset. Do not use Reset for routine receipts or issues.') . '</p>',
        ],
      ],
      'actions' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
      '#cache' => ['contexts' => ['user.permissions']],
    ];
  }

}
