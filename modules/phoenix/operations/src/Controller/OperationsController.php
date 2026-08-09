<?php

declare(strict_types=1);

namespace Drupal\phoenix_operations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\phoenix_operations\Service\OperationsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the Phoenix Operations Centre.
 */
final class OperationsController extends ControllerBase {

  /**
   * Constructs the controller.
   */
  public function __construct(
    private readonly OperationsService $operationsService,
  ) {}

  /**
   * Creates the controller from the service container.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('phoenix_operations.operations'),
    );
  }

  /**
   * Displays the Operations Centre.
   */
  public function collection(): array {
    $operations = $this->operationsService->getRecentOperations();

    foreach ($operations as &$operation) {
      $operation['url'] = Url::fromRoute(
        'phoenix_operations.workspace',
        [
          'log' => $operation['id'],
        ],
      )->toString();
    }

    $operationsUrl = Url::fromRoute(
      'phoenix_operations.collection',
      [],
      [
        'absolute' => TRUE,
      ],
    )->toString();

    $recordOperationUrl = Url::fromRoute(
      'entity.log.add_form',
      [
        'log_type' => 'activity',
      ],
      [
        'query' => [
          'destination' => $operationsUrl,
        ],
      ],
    )->toString();

    return [
      '#theme' => 'phoenix_operations_collection',
      '#operations' => $operations,
      '#record_operation_url' => $recordOperationUrl,
      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],
      '#cache' => [
        'tags' => [
          'log_list',
        ],
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];
  }

}
