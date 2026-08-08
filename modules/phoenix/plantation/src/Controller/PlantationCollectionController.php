<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Builds the Phoenix Plantations collection page.
 */
final class PlantationCollectionController extends ControllerBase {

  /**
   * Displays all Phoenix plantation blocks.
   */
  public function collection(): array {
    $assetStorage = $this->entityTypeManager()->getStorage('asset');

    $ids = $assetStorage->getQuery()
      ->condition('type', 'land')
      ->condition('land_type', 'plantation_block')
      ->sort('name', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $blocks = $assetStorage->loadMultiple($ids);

    $rows = [];

    foreach ($blocks as $block) {
      $estateName = 'Not assigned';

      if (
        $block->hasField('parent') &&
        !$block->get('parent')->isEmpty()
      ) {
        $estate = $block->get('parent')->entity;

        if ($estate !== NULL) {
          $estateName = $estate->label();
        }
      }

      $phoenixCode = 'Not assigned';

      if (
        $block->hasField('field_phoenix_code') &&
        !$block->get('field_phoenix_code')->isEmpty()
      ) {
        $phoenixCode = $block->get('field_phoenix_code')->value;
      }

      $treeCount = '—';

      if (
        $block->hasField('field_tree_count') &&
        !$block->get('field_tree_count')->isEmpty()
      ) {
        $treeCount = number_format(
          (int) $block->get('field_tree_count')->value
        );
      }

      $rows[] = [
        'name' => $block->label(),
        'estate' => $estateName,
        'code' => $phoenixCode,
        'tree_count' => $treeCount,
        'health' => 'Not assessed',
        'url' => Url::fromRoute(
          'phoenix_plantation.workspace',
          ['asset' => $block->id()],
        )->toString(),
      ];
    }

    return [
      '#theme' => 'phoenix_plantation_collection',
      '#blocks' => $rows,
      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],
      '#cache' => [
        'tags' => ['asset_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
