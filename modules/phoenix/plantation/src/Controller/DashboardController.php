<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\phoenix_plantation\Service\DashboardService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the Phoenix OS Command Centre.
 */
final class DashboardController extends ControllerBase {

  public function __construct(
    private readonly DashboardService $dashboardService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('phoenix_plantation.dashboard'),
    );
  }

  /**
   * Displays the Phoenix OS Command Centre.
   */
  public function dashboard(): array {
    $data = $this->dashboardService->getDashboardData();

    $operationCount = (int) \Drupal::entityQuery('log')
      ->condition('type', 'activity')
      ->accessCheck(TRUE)
      ->count()
      ->execute();

    return [
      '#theme' => 'phoenix_dashboard',

      '#estate_count' => $data['estate_count'],
      '#block_count' => $data['block_count'],
      '#operation_count' => $operationCount,
      '#estate_rows' => $data['estate_rows'],

      '#estates_url' => Url::fromRoute(
        'phoenix_estate.collection',
      )->toString(),

      '#plantations_url' => Url::fromRoute(
        'phoenix_plantation.collection',
      )->toString(),

      '#operations_url' => Url::fromRoute(
        'phoenix_operations.collection',
      )->toString(),

      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],

      '#cache' => [
        'tags' => [
          'asset_list',
          'log_list',
        ],
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];
  }

}
