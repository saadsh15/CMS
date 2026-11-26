<?php

namespace Drupal\portfolio_core\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Discovers all portfolio platform modules via hook_portfolio_module_info().
 */
class ModuleRegistry {

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Returns all registered modules across enabled Drupal extensions.
   */
  public function getModules(): array {
    $modules = $this->moduleHandler->invokeAll('portfolio_module_info');
    $normalized = [];

    foreach ($modules as $key => $info) {
      if (!is_array($info)) {
        continue;
      }
      $id = (string) ($info['id'] ?? $key);
      $normalized[] = [
        'id' => $id,
        'name' => (string) ($info['name'] ?? $id),
        'description' => (string) ($info['description'] ?? ''),
        'version' => (string) ($info['version'] ?? '1.0.0'),
        'route' => (string) ($info['route'] ?? "/m/{$id}"),
        'api_endpoint' => $info['api_endpoint'] ?? NULL,
        'icon' => (string) ($info['icon'] ?? 'puzzle'),
        'show_in_nav' => (bool) ($info['show_in_nav'] ?? FALSE),
        'embeddable' => (bool) ($info['embeddable'] ?? FALSE),
        'widgets' => array_values((array) ($info['widgets'] ?? [])),
        'status' => (string) ($info['status'] ?? 'active'),
      ];
    }

    return $normalized;
  }

}
