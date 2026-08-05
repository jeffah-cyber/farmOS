<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Service;

use Drupal\asset\Entity\AssetInterface;

/**
 * Provides data for an individual Plantation Block Workspace.
 */
final class PlantationWorkspaceService {

  /**
   * Builds workspace data for a Plantation Block.
   */
  public function getWorkspaceData(AssetInterface $block): array {
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

    return [
      'block_name' => $block->label(),
      'phoenix_code' => $phoenixCode,
      'estate_name' => $estateName,
      'tree_count' => $treeCount,
      'area' => '—',
      'age' => '—',
      'health' => 'Not assessed',
    ];
  }

}
