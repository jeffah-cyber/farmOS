<?php

declare(strict_types=1);

namespace Drupal\phoenix_estate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Builds the Phoenix Estates collection page.
 */
final class EstateCollectionController extends ControllerBase {

  /**
   * Displays all Phoenix estates.
   */
  public function collection(): array {
    $assetStorage = $this->entityTypeManager()->getStorage('asset');

    $ids = $assetStorage->getQuery()
      ->condition('type', 'land')
      ->condition('land_type', 'estate')
      ->sort('name', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $estates = $assetStorage->loadMultiple($ids);

    $rows = [];

    foreach ($estates as $estate) {
      $phoenixCode = 'Not assigned';

      if (
        $estate->hasField('field_phoenix_code') &&
        !$estate->get('field_phoenix_code')->isEmpty()
      ) {
        $phoenixCode = $estate->get('field_phoenix_code')->value;
      }

      $lifecycle = 'Not assigned';

      if (
        $estate->hasField('field_estate_lifecycle') &&
        !$estate->get('field_estate_lifecycle')->isEmpty()
      ) {
        $term = $estate->get('field_estate_lifecycle')->entity;

        if ($term !== NULL) {
          $lifecycle = $term->label();
        }
      }

      $rows[] = [
        'name' => $estate->label(),
        'code' => $phoenixCode,
        'lifecycle' => $lifecycle,
        'url' => Url::fromRoute(
          'phoenix_estate.workspace',
          ['asset' => $estate->id()],
        )->toString(),
      ];
    }

    return [
      '#theme' => 'phoenix_estate_collection',
      '#estates' => $rows,
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
