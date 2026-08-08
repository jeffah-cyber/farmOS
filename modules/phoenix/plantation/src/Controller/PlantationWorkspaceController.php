<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Controller;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\phoenix_plantation\Service\PlantationWorkspaceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the Plantation Block Workspace.
 */
final class PlantationWorkspaceController extends ControllerBase {

  /**
   * Constructs the controller.
   */
  public function __construct(
    private readonly PlantationWorkspaceService $workspaceService,
  ) {}

  /**
   * Creates the controller from the service container.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('phoenix_plantation.workspace'),
    );
  }

  /**
   * Returns the page title.
   */
  public function title(AssetInterface $asset): string {
    $this->validateBlock($asset);

    return $asset->label();
  }

  /**
   * Displays the Plantation Block Workspace.
   */
  public function workspace(AssetInterface $asset): array {
    $this->validateBlock($asset);

    $data = $this->workspaceService->getWorkspaceData($asset);

    return [
      '#theme' => 'phoenix_plantation_workspace',
      '#block_name' => $data['block_name'],
      '#phoenix_code' => $data['phoenix_code'],
      '#estate_name' => $data['estate_name'],
      '#tree_count' => $data['tree_count'],
      '#area' => $data['area'],
      '#age' => $data['age'],
      '#health' => $data['health'],
      '#recent_activities' => $data['recent_activities'],
      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],
      '#cache' => [
        'tags' => array_merge(
          $asset->getCacheTags(),
          ['asset_list', 'log_list'],
        ),
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];
  }

  /**
   * Ensures the supplied asset is a Plantation Block.
   */
  private function validateBlock(AssetInterface $asset): void {
    if (
      $asset->bundle() !== 'land' ||
      !$asset->hasField('land_type') ||
      $asset->get('land_type')->isEmpty() ||
      $asset->get('land_type')->value !== 'plantation_block'
    ) {
      throw new NotFoundHttpException();
    }
  }

}
