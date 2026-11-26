<?php

namespace Drupal\portfolio_lab_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides live Build Lab module telemetry derived from CMS projects & blogs.
 */
class LabController extends ControllerBase {

  /**
   * Returns live build lab metrics & active sprint experiments.
   */
  public function status(): JsonResponse {
    $nodeStorage = $this->entityTypeManager()->getStorage('node');

    $projectIds = $nodeStorage->getQuery()
      ->condition('type', 'project')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $blogCount = (int) $nodeStorage->getQuery()
      ->condition('type', 'blog_post')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $pageCount = (int) $nodeStorage->getQuery()
      ->condition('type', 'portfolio_page')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $projects = $projectIds ? $nodeStorage->loadMultiple($projectIds) : [];
    $activeExperiments = [];
    $shippedCount = 0;
    $inProgressCount = 0;

    foreach ($projects as $project) {
      $status = $project->get('field_status')->value ?? 'in_progress';
      if ($status === 'launched') {
        $shippedCount++;
      }
      if ($status === 'in_progress' || $status === 'research') {
        $inProgressCount++;
        $activeExperiments[] = [
          'id' => (int) $project->id(),
          'slug' => $project->get('field_slug')->value ?? '',
          'title' => $project->label(),
          'status' => $status,
          'progress' => (int) ($project->get('field_progress')->value ?? 0),
          'milestone' => $project->get('field_current_milestone')->value ?? 'Iterating on core architecture',
          'accent' => $project->get('field_accent')->value ?? 'cyan',
        ];
      }
    }

    $response = new JsonResponse([
      'module' => 'lab-status',
      'availability' => 'Open to Full-Stack, Backend & Applied AI Engineering Roles',
      'current_focus' => 'Enterprise utility systems, Agentic RAG workflows, PyTorch Computer Vision & modular CMS platforms',
      'metrics' => [
        'active_builds' => $inProgressCount,
        'shipped_projects' => $shippedCount,
        'published_articles' => $blogCount,
        'modular_pages' => $pageCount,
      ],
      'active_experiments' => $activeExperiments,
      'updated_at' => gmdate('c'),
    ]);
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    return $response;
  }

}
