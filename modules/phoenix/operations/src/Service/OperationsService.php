<?php

declare(strict_types=1);

namespace Drupal\phoenix_operations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides operational data for Phoenix OS.
 */
final class OperationsService {

  /**
   * Constructs the Operations service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns recent Phoenix operations.
   */
  public function getRecentOperations(int $limit = 25): array {
    $logStorage = $this->entityTypeManager->getStorage('log');

    $ids = $logStorage->getQuery()
      ->condition('type', 'activity')
      ->sort('timestamp', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE)
      ->execute();

    $logs = $logStorage->loadMultiple($ids);

    $operations = [];

    foreach ($logs as $log) {

      $categories = [];

foreach ($log->get('category')->referencedEntities() as $category) {
  $categories[] = $category->label();
}

      $assets = [];

      foreach ($log->get('asset')->referencedEntities() as $asset) {
        $assets[] = [
          'id' => $asset->id(),
          'name' => $asset->label(),
        ];
      }

      $operations[] = [
        'id' => $log->id(),
        'name' => $log->label(),
        'timestamp' => (int) $log->get('timestamp')->value,
        'status' => $log->get('status')->value ?? '',
        'categories' => $categories, 
        'assets' => $assets,
      ];
    }

    return $operations;
  }

}
