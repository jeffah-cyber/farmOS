<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides data for the Phoenix Plantation dashboard.
 */
final class DashboardService {

  /**
   * Constructs the dashboard service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns estate and plantation block dashboard data.
   */
  public function getDashboardData(): array {
    $storage = $this->entityTypeManager->getStorage('asset');

    $ids = $storage->getQuery()
      ->condition('type', 'land')
      ->sort('name', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $assets = $storage->loadMultiple($ids);

    $estateRows = [];
    $estateCount = 0;
    $blockCount = 0;

    foreach ($assets as $asset) {
      if (
        !$asset->hasField('land_type') ||
        $asset->get('land_type')->isEmpty()
      ) {
        continue;
      }

      $landType = $asset->get('land_type')->value;

      if ($landType === 'plantation_block') {
        $blockCount++;
        continue;
      }

      if ($landType !== 'estate') {
        continue;
      }

      $estateCount++;

      $phoenixCode = 'Not assigned';
      if (
        $asset->hasField('field_phoenix_code') &&
        !$asset->get('field_phoenix_code')->isEmpty()
      ) {
        $phoenixCode = $asset->get('field_phoenix_code')->value;
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

      $estateRows[] = [
        'name' => $asset->toLink($asset->label()),
        'code' => $phoenixCode,
        'lifecycle' => $lifecycle,
      ];
    }

    return [
      'estate_count' => $estateCount,
      'block_count' => $blockCount,
      'estate_rows' => $estateRows,
    ];
  }

}
