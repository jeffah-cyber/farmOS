<?php

declare(strict_types=1);

namespace Drupal\phoenix_estate\Controller;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\phoenix_estate\Service\EstateWorkspaceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the Phoenix Estate Workspace.
 */
final class EstateWorkspaceController extends ControllerBase {

  /**
   * Constructs the Estate Workspace controller.
   */
  public function __construct(
    private readonly EstateWorkspaceService $workspaceService,
  ) {}

  /**
   * Creates the controller from Drupal's service container.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('phoenix_estate.workspace'),
    );
  }

  /**
   * Returns the Estate Workspace page title.
   */
  public function title(AssetInterface $asset): string {
    $this->validateEstate($asset);

    return $asset->label();
  }

  /**
   * Displays the Estate Workspace.
   */
  public function workspace(AssetInterface $asset): array {
    $this->validateEstate($asset);

    $data = $this->workspaceService->getWorkspaceData($asset);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['phoenix-estate-workspace'],
      ],

      'header' => [
        '#markup' => '<h1>' . $data['estate_name'] . '</h1>',
      ],

      'details' => [
        '#theme' => 'item_list',
        '#title' => $this->t('Estate Information'),
        '#items' => [
          $this->t('Phoenix Code: @code', [
            '@code' => $data['phoenix_code'],
          ]),
          $this->t('Lifecycle: @lifecycle', [
            '@lifecycle' => $data['lifecycle'],
          ]),
        ],
      ],

      'operations' => [
        '#theme' => 'item_list',
        '#title' => $this->t('Operations'),
        '#items' => [
          $this->t('Plantation Blocks: @count', [
            '@count' => $data['block_count'],
          ]),
          $this->t('Workers: Pending'),
          $this->t('Machinery: Pending'),
          $this->t('Tasks Today: Pending'),
        ],
      ],

      'activity' => [
        '#markup' => '<h2>Recent Activity</h2><p>No activity recorded.</p>',
      ],

      'alerts' => [
        '#markup' => '<h2>Alerts</h2><p>No active alerts.</p>',
      ],

      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],

      '#cache' => [
        'tags' => array_merge(
          $asset->getCacheTags(),
          ['asset_list'],
        ),
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];
  }

  /**
   * Ensures the supplied asset is an Estate.
   */
  private function validateEstate(AssetInterface $asset): void {
    if (
      $asset->bundle() !== 'land' ||
      !$asset->hasField('land_type') ||
      $asset->get('land_type')->isEmpty() ||
      $asset->get('land_type')->value !== 'estate'
    ) {
      throw new NotFoundHttpException();
    }
  }

}
