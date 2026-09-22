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
    $plantationBlocks = [];
    $equipment = [];

    foreach ($log->get('asset')->referencedEntities() as $asset) {
      $assetData = [
        'id' => $asset->id(),
        'name' => $asset->label(),
      ];

      if ($asset->bundle() === 'equipment') {
        $equipment[] = $assetData;
        continue;
      }

      if (
        $asset->bundle() === 'land'
        && $asset->hasField('land_type')
        && $asset->get('land_type')->value === 'plantation_block'
      ) {
        $plantationBlocks[] = $assetData;
        continue;
      }

      // Preserve any other farmOS assets rather than silently discarding them.
      $assets[] = $assetData;
    }

    $workers = [];

    foreach ($log->get('owner')->referencedEntities() as $owner) {
      $workers[] = $owner->getDisplayName();
    }

    $notes = '';

    if (
      $log->hasField('notes')
      && !$log->get('notes')->isEmpty()
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
      'plantation_blocks' => $plantationBlocks,
      'equipment' => $equipment,
      'assets' => $assets,
      'workers' => $workers,
      'is_movement' => (bool) $log->get('is_movement')->value,
      'categories' => $categories,
    ];
  }

}
