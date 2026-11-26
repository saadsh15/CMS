<?php

namespace Drupal\portfolio_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\portfolio_core\Service\ContentSerializer;
use Drupal\portfolio_core\Service\ModuleRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Clean headless JSON API for Next.js frontend & future platform modules.
 */
class PortfolioApiController extends ControllerBase {

  public function __construct(
    protected ContentSerializer $serializer,
    protected ModuleRegistry $moduleRegistry,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('portfolio_core.content_serializer'),
      $container->get('portfolio_core.module_registry'),
    );
  }

  /**
   * Wraps payload with non-stale headers so Next.js controls caching & ISR.
   */
  protected function json(array $data, int $status = 200): JsonResponse {
    $response = new JsonResponse($data, $status);
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    return $response;
  }

  /**
   * Site bootstrap manifest: navigation pages, registered modules, and stats.
   */
  public function bootstrap(): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $pageIds = $storage->getQuery()
      ->condition('type', 'portfolio_page')
      ->condition('status', 1)
      ->condition('field_show_in_nav', 1)
      ->sort('field_nav_order', 'ASC')
      ->accessCheck(FALSE)
      ->execute();

    $navPages = [];
    if ($pageIds) {
      foreach ($storage->loadMultiple($pageIds) as $node) {
        $navPages[] = $this->serializer->serializePage($node, FALSE);
      }
    }

    $appearance = $this->config('portfolio_core.appearance');

    return $this->json([
      'siteName' => $this->config('system.site')->get('name') ?: 'Portfolio Studio',
      'navPages' => $navPages,
      'modules' => $this->moduleRegistry->getModules(),
      'typography' => [
        'linkFont' => $appearance->get('link_font') ?: 'jakarta',
      ],
      'cms' => [
        'engine' => 'Drupal 11',
        'jsonApiRoot' => '/jsonapi',
      ],
    ]);
  }

  /**
   * Lists all registered platform modules (core + future extensions).
   */
  public function modules(): JsonResponse {
    return $this->json([
      'items' => $this->moduleRegistry->getModules(),
    ]);
  }

  /**
   * Lists all published modular pages.
   */
  public function pages(): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'portfolio_page')
      ->condition('status', 1)
      ->sort('field_nav_order', 'ASC')
      ->accessCheck(FALSE)
      ->execute();

    $items = [];
    if ($ids) {
      foreach ($storage->loadMultiple($ids) as $node) {
        $items[] = $this->serializer->serializePage($node, FALSE);
      }
    }

    return $this->json(['items' => $items]);
  }

  /**
   * Fetches a single modular page and all its Paragraph sections by slug.
   */
  public function pageBySlug(string $slug): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'portfolio_page')
      ->condition('status', 1)
      ->condition('field_slug', $slug)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return $this->json(['error' => 'Page not found', 'slug' => $slug], 404);
    }

    $node = $storage->load(reset($ids));
    return $this->json($this->serializer->serializePage($node, TRUE));
  }

  /**
   * Lists published projects with optional status / featured / limit filters.
   */
  public function projects(Request $request): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'project')
      ->condition('status', 1)
      ->sort('field_featured', 'DESC')
      ->sort('changed', 'DESC')
      ->accessCheck(FALSE);

    $status = $request->query->get('status');
    if ($status && $status !== 'all') {
      $query->condition('field_status', $status);
    }

    if ($request->query->get('featured') === '1' || $request->query->get('featured') === 'true') {
      $query->condition('field_featured', 1);
    }

    $limit = (int) $request->query->get('limit', 0);
    if ($limit > 0) {
      $query->range(0, min($limit, 50));
    }

    $ids = $query->execute();
    $items = [];
    if ($ids) {
      foreach ($storage->loadMultiple($ids) as $node) {
        $items[] = $this->serializer->serializeProject($node, FALSE);
      }
    }

    return $this->json(['items' => $items]);
  }

  /**
   * Fetches a full project case study by slug.
   */
  public function projectBySlug(string $slug): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'project')
      ->condition('status', 1)
      ->condition('field_slug', $slug)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return $this->json(['error' => 'Project not found', 'slug' => $slug], 404);
    }

    $node = $storage->load(reset($ids));
    return $this->json($this->serializer->serializeProject($node, TRUE));
  }

  /**
   * Lists published blog posts with optional featured / limit filters.
   */
  public function blogs(Request $request): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'blog_post')
      ->condition('status', 1)
      ->sort('field_published_date', 'DESC')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);

    if ($request->query->get('featured') === '1' || $request->query->get('featured') === 'true') {
      $query->condition('field_featured', 1);
    }

    $limit = (int) $request->query->get('limit', 0);
    if ($limit > 0) {
      $query->range(0, min($limit, 50));
    }

    $ids = $query->execute();
    $items = [];
    if ($ids) {
      foreach ($storage->loadMultiple($ids) as $node) {
        $items[] = $this->serializer->serializeBlog($node, FALSE);
      }
    }

    return $this->json(['items' => $items]);
  }

  /**
   * Fetches a full blog article by slug.
   */
  public function blogBySlug(string $slug): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'blog_post')
      ->condition('status', 1)
      ->condition('field_slug', $slug)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return $this->json(['error' => 'Blog post not found', 'slug' => $slug], 404);
    }

    $node = $storage->load(reset($ids));
    return $this->json($this->serializer->serializeBlog($node, TRUE));
  }

}
