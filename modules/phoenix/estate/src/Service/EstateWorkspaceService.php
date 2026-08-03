<?php

declare(strict_types=1);

namespace Drupal\phoenix_estate\Service;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides data for an individual Estate Workspace.
 */
final class EstateWorkspaceService {

  /**
   * Constructs the Estate Workspace service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Builds workspace data for an Estate.
   */
  public function getWorkspaceData(AssetInterface $estate): array {
    $assetStorage = $this->entityTypeManager->getStorage('asset');

    $blockIds = $assetStorage->getQuery()
      ->condition('type', 'land')
      ->condition('land_type', 'plantation_block')
      ->condition('parent.target_id', $estate->id())
      ->accessCheck(TRUE)
      ->execute();

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

    return [
      'estate_name' => $estate->label(),
      'phoenix_code' => $phoenixCode,
      'lifecycle' => $lifecycle,
      'block_count' => count($blockIds),
    ];
  }

}
