<?php

declare(strict_types=1);

namespace Drupal\phoenix_operations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\log\Entity\LogInterface;

/**
 * Provides data for an individual Phoenix Operation Workspace.
 */
final class OperationWorkspaceService {

  /**
   * Constructs the service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Builds workspace data for an operation.
   */
  public function getWorkspaceData(LogInterface $log): array {
    $assets = [];

    foreach ($log->get('asset')->referencedEntities() as $asset) {
      $assets[] = [
        'id' => $asset->id(),
        'name' => $asset->label(),
      ];
    }

    $owners = [];

    foreach ($log->get('owner')->referencedEntities() as $owner) {
      $owners[] = $owner->label();
    }

    $notes = '';

    if (
      $log->hasField('notes') &&
      !$log->get('notes')->isEmpty()
    ) {
      $notes = $log->get('notes')->value;
    }

    $categories = [];

foreach ($log->get('category')->referencedEntities() as $category) {
  $categories[] = $category->label();
}

    return [
      'id' => $log->id(),
      'name' => $log->label(),
      'status' => $log->get('status')->value ?? '',
      'timestamp' => (int) $log->get('timestamp')->value,
      'notes' => $notes,
      'assets' => $assets,
      'owners' => $owners,
      'is_movement' => (bool) $log->get('is_movement')->value,
      'categories' => $categories,
    ];
  }

}
