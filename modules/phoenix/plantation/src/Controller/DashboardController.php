<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Builds the Phoenix Plantation dashboard.
 */
final class DashboardController extends ControllerBase {

  /**
   * Displays live estate and plantation-block information.
   */
  public function dashboard(): array {
    $asset_storage = $this->entityTypeManager()->getStorage('asset');

    // Load all Land assets the current user is allowed to view.
    $asset_ids = $asset_storage->getQuery()
      ->condition('type', 'land')
      ->sort('name', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $assets = $asset_storage->loadMultiple($asset_ids);

    $estate_rows = [];
    $estate_count = 0;
    $block_count = 0;

    foreach ($assets as $asset) {
      if (!$asset->hasField('land_type') || $asset->get('land_type')->isEmpty()) {
        continue;
      }

      $land_type = $asset->get('land_type')->value;

      if ($land_type === 'plantation_block') {
        $block_count++;
        continue;
      }

      if ($land_type !== 'estate') {
        continue;
      }

      $estate_count++;

      $phoenix_code = 'Not assigned';
      if (
        $asset->hasField('field_phoenix_code') &&
        !$asset->get('field_phoenix_code')->isEmpty()
      ) {
        $phoenix_code = $asset->get('field_phoenix_code')->value;
      }

      $lifecycle = 'Not assigned';
      if (
        $asset->hasField('field_estate_lifecycle') &&
        !$asset->get('field_estate_lifecycle')->isEmpty()
      ) {
        $term = $asset->get('field_estate_lifecycle')->entity;

        if ($term !== NULL) {
          $lifecycle = $term->label();
        }
      }

      $estate_rows[] = [
        'name' => $asset->toLink($asset->label()),
        'code' => $phoenix_code,
        'lifecycle' => $lifecycle,
      ];
    }

    return [
      'intro' => [
        '#markup' => '<p>Live operational summary for Phoenix estates.</p>',
      ],

      'summary' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['phoenix-dashboard-summary'],
        ],
        'estates' => [
          '#markup' => '<h2>Total Estates: ' . $estate_count . '</h2>',
        ],
        'blocks' => [
          '#markup' => '<h2>Plantation Blocks: ' . $block_count . '</h2>',
        ],
      ],

      'estate_heading' => [
        '#markup' => '<h2>Estate Portfolio</h2>',
      ],

      'estate_table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Estate'),
          $this->t('Phoenix Code'),
          $this->t('Lifecycle'),
        ],
        '#rows' => $estate_rows,
        '#empty' => $this->t('No estates have been created yet.'),
      ],

      '#cache' => [
        'tags' => ['asset_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
