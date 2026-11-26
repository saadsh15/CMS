<?php

namespace Drupal\portfolio_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Serializes Drupal Nodes & Paragraph Page Builder blocks into clean JSON.
 */
class ContentSerializer {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Extracts a scalar field value safely.
   */
  protected function scalar(object $entity, string $field, mixed $default = NULL): mixed {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return $default;
    }
    return $entity->get($field)->value;
  }

  /**
   * Extracts a formatted text field value safely.
   */
  protected function richText(object $entity, string $field): string {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return '';
    }
    $item = $entity->get($field)->first();
    return (string) ($item->processed ?? $item->value ?? '');
  }

  /**
   * Extracts referenced taxonomy term names.
   */
  protected function termNames(NodeInterface $node, string $field): array {
    if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
      return [];
    }
    $names = [];
    foreach ($node->get($field)->referencedEntities() as $term) {
      $names[] = $term->label();
    }
    return $names;
  }

  /**
   * Serializes a Project node.
   */
  public function serializeProject(NodeInterface $node, bool $includeBody = FALSE): array {
    $data = [
      'id' => (int) $node->id(),
      'slug' => (string) $this->scalar($node, 'field_slug', 'project-' . $node->id()),
      'title' => $node->label(),
      'role' => (string) $this->scalar($node, 'field_role', ''),
      'summary' => (string) $this->scalar($node, 'field_summary', ''),
      'status' => (string) $this->scalar($node, 'field_status', 'in_progress'),
      'progress' => (int) $this->scalar($node, 'field_progress', 0),
      'currentMilestone' => (string) $this->scalar($node, 'field_current_milestone', ''),
      'featured' => (bool) $this->scalar($node, 'field_featured', FALSE),
      'accent' => (string) $this->scalar($node, 'field_accent', 'cyan'),
      'techStack' => $this->termNames($node, 'field_tech_stack'),
      'repoUrl' => $this->scalar($node, 'field_repo_url'),
      'liveUrl' => $this->scalar($node, 'field_live_url'),
      'coverUrl' => $this->scalar($node, 'field_cover_url'),
      'updatedAt' => gmdate('c', (int) $node->getChangedTime()),
    ];

    if ($includeBody) {
      $data['bodyHtml'] = $this->richText($node, 'field_body');
      $data['sections'] = $this->serializeSections($node, 'field_sections');
    }

    return $data;
  }

  /**
   * Serializes a Blog Post node.
   */
  public function serializeBlog(NodeInterface $node, bool $includeBody = FALSE): array {
    $categories = $this->termNames($node, 'field_category');
    $publishedDate = (string) $this->scalar($node, 'field_published_date', gmdate('Y-m-d', (int) $node->getCreatedTime()));

    $data = [
      'id' => (int) $node->id(),
      'slug' => (string) $this->scalar($node, 'field_slug', 'post-' . $node->id()),
      'title' => $node->label(),
      'summary' => (string) $this->scalar($node, 'field_summary', ''),
      'category' => $categories[0] ?? 'Engineering',
      'tags' => $this->termNames($node, 'field_tags'),
      'readingTime' => (int) $this->scalar($node, 'field_reading_time', 5),
      'featured' => (bool) $this->scalar($node, 'field_featured', FALSE),
      'publishedDate' => $publishedDate,
      'coverUrl' => $this->scalar($node, 'field_cover_url'),
      'updatedAt' => gmdate('c', (int) $node->getChangedTime()),
    ];

    if ($includeBody) {
      $data['bodyHtml'] = $this->richText($node, 'field_body');
    }

    return $data;
  }

  /**
   * Serializes a Modular Page node.
   */
  public function serializePage(NodeInterface $node, bool $includeSections = TRUE): array {
    $data = [
      'id' => (int) $node->id(),
      'slug' => (string) $this->scalar($node, 'field_slug', 'page-' . $node->id()),
      'title' => $node->label(),
      'metaDescription' => (string) $this->scalar($node, 'field_meta_description', ''),
      'showInNav' => (bool) $this->scalar($node, 'field_show_in_nav', FALSE),
      'navOrder' => (int) $this->scalar($node, 'field_nav_order', 10),
      'updatedAt' => gmdate('c', (int) $node->getChangedTime()),
    ];

    if ($includeSections) {
      $data['sections'] = $this->serializeSections($node, 'field_sections');
    }

    return $data;
  }

  /**
   * Serializes all Paragraph blocks on an entity field.
   */
  public function serializeSections(object $entity, string $field): array {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return [];
    }

    $sections = [];
    foreach ($entity->get($field)->referencedEntities() as $paragraph) {
      if ($paragraph instanceof ParagraphInterface) {
        $sections[] = $this->serializeParagraph($paragraph);
      }
    }

    return $sections;
  }

  /**
   * Serializes reusable background settings on supported Paragraph blocks.
   */
  protected function serializeBackground(ParagraphInterface $p): ?array {
    $bgType = $this->scalar($p, 'field_bg_type');
    $mediaUrl = $this->scalar($p, 'field_bg_media_url');
    if (!$bgType && !$mediaUrl) {
      return NULL;
    }

    return [
      'type' => (string) ($bgType ?: 'architectural_topo'),
      'mediaUrl' => $mediaUrl ? (string) $mediaUrl : NULL,
      'posterUrl' => $this->scalar($p, 'field_bg_poster_url') ? (string) $this->scalar($p, 'field_bg_poster_url') : NULL,
      'overlay' => (string) ($this->scalar($p, 'field_bg_overlay') ?: 'medium'),
    ];
  }

  /**
   * Serializes an individual Paragraph block according to its bundle type.
   */
  public function serializeParagraph(ParagraphInterface $p): array {
    $type = $p->bundle();
    $base = [
      'id' => (int) $p->id(),
      'type' => $type,
    ];

    return match ($type) {
      'hero_section' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'highlightText' => (string) $this->scalar($p, 'field_highlight_text', ''),
        'subtitle' => (string) $this->scalar($p, 'field_subtitle', ''),
        'primaryCta' => $this->scalar($p, 'field_cta_primary_label') ? [
          'label' => (string) $this->scalar($p, 'field_cta_primary_label'),
          'url' => (string) $this->scalar($p, 'field_cta_primary_url', '#'),
        ] : NULL,
        'secondaryCta' => $this->scalar($p, 'field_cta_secondary_label') ? [
          'label' => (string) $this->scalar($p, 'field_cta_secondary_label'),
          'url' => (string) $this->scalar($p, 'field_cta_secondary_url', '#'),
        ] : NULL,
        'variant' => (string) $this->scalar($p, 'field_variant', 'spotlight'),
        'profileCard' => ($this->scalar($p, 'field_portrait_url') || $this->scalar($p, 'field_card_quote') || $this->scalar($p, 'field_card_name')) ? [
          'portraitUrl' => $this->scalar($p, 'field_portrait_url') ? (string) $this->scalar($p, 'field_portrait_url') : NULL,
          'name' => (string) $this->scalar($p, 'field_card_name', ''),
          'tagline' => (string) $this->scalar($p, 'field_card_tagline', ''),
          'quote' => (string) $this->scalar($p, 'field_card_quote', ''),
          'caption' => (string) $this->scalar($p, 'field_card_caption', ''),
        ] : NULL,
        'background' => $this->serializeBackground($p),
      ],

      'background_banner' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'subtitle' => (string) $this->scalar($p, 'field_subtitle', ''),
        'primaryCta' => $this->scalar($p, 'field_cta_primary_label') ? [
          'label' => (string) $this->scalar($p, 'field_cta_primary_label'),
          'url' => (string) $this->scalar($p, 'field_cta_primary_url', '#'),
        ] : NULL,
        'secondaryCta' => $this->scalar($p, 'field_cta_secondary_label') ? [
          'label' => (string) $this->scalar($p, 'field_cta_secondary_label'),
          'url' => (string) $this->scalar($p, 'field_cta_secondary_url', '#'),
        ] : NULL,
        'height' => (string) $this->scalar($p, 'field_height', 'medium'),
        'background' => $this->serializeBackground($p),
      ],

      'bento_grid' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'subtitle' => (string) $this->scalar($p, 'field_subtitle', ''),
        'layout' => (string) $this->scalar($p, 'field_layout', 'asymmetric'),
        'items' => $this->serializeSections($p, 'field_bento_items'),
      ],

      'bento_item' => $base + [
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'description' => (string) $this->scalar($p, 'field_subtitle', ''),
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'metric' => (string) $this->scalar($p, 'field_metric', ''),
        'icon' => (string) $this->scalar($p, 'field_icon', 'sparkles'),
        'span' => (string) $this->scalar($p, 'field_span', '1x1'),
        'accent' => (string) $this->scalar($p, 'field_accent', 'cyan'),
        'linkUrl' => $this->scalar($p, 'field_link_url'),
        'linkLabel' => $this->scalar($p, 'field_link_label'),
        'imageUrl' => $this->scalar($p, 'field_image_url'),
      ],

      'showcase_block' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'subtitle' => (string) $this->scalar($p, 'field_subtitle', ''),
        'source' => (string) $this->scalar($p, 'field_source', 'projects'),
        'filter' => (string) $this->scalar($p, 'field_filter', 'all'),
        'limit' => (int) $this->scalar($p, 'field_limit', 4),
        'linkUrl' => $this->scalar($p, 'field_link_url'),
        'linkLabel' => $this->scalar($p, 'field_link_label'),
      ],

      'rich_text_block' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'bodyHtml' => $this->richText($p, 'field_body'),
        'width' => (string) $this->scalar($p, 'field_width', 'medium'),
      ],

      'stats_strip' => $base + [
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'items' => $this->serializeSections($p, 'field_stat_items'),
      ],

      'stat_item' => $base + [
        'metric' => (string) $this->scalar($p, 'field_metric', ''),
        'label' => (string) $this->scalar($p, 'field_title', ''),
        'caption' => (string) $this->scalar($p, 'field_subtitle', ''),
      ],

      'media_gallery' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'items' => $this->serializeSections($p, 'field_media_items'),
      ],

      'media_item' => $base + [
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'caption' => (string) $this->scalar($p, 'field_subtitle', ''),
        'imageUrl' => (string) $this->scalar($p, 'field_image_url', ''),
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
      ],

      'cta_banner' => $base + [
        'eyebrow' => (string) $this->scalar($p, 'field_eyebrow', ''),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'subtitle' => (string) $this->scalar($p, 'field_subtitle', ''),
        'primaryCta' => $this->scalar($p, 'field_cta_primary_label') ? [
          'label' => (string) $this->scalar($p, 'field_cta_primary_label'),
          'url' => (string) $this->scalar($p, 'field_cta_primary_url', '#'),
        ] : NULL,
        'secondaryCta' => $this->scalar($p, 'field_cta_secondary_label') ? [
          'label' => (string) $this->scalar($p, 'field_cta_secondary_label'),
          'url' => (string) $this->scalar($p, 'field_cta_secondary_url', '#'),
        ] : NULL,
        'accent' => (string) $this->scalar($p, 'field_accent', 'cyan'),
        'background' => $this->serializeBackground($p),
      ],

      'module_embed' => $base + [
        'moduleId' => (string) $this->scalar($p, 'field_module_id', ''),
        'widget' => (string) $this->scalar($p, 'field_widget_name', 'primary'),
        'title' => (string) $this->scalar($p, 'field_title', ''),
        'config' => $this->decodeJson((string) $this->scalar($p, 'field_config_json', '')),
      ],

      default => $base,
    };
  }

  /**
   * Decodes optional JSON config safely.
   */
  protected function decodeJson(string $raw): array {
    if (trim($raw) === '') {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
