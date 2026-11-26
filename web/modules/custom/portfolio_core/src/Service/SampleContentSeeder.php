<?php

namespace Drupal\portfolio_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

/**
 * Seeds Saad Shafique's portfolio pages, real projects, screenshots, and blog posts.
 */
class SampleContentSeeder {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Runs all seeders idempotently. When $force is TRUE, replaces existing portfolio nodes cleanly.
   */
  public function seedAll(bool $force = FALSE): void {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $existingIds = $nodeStorage->getQuery()
      ->condition('type', ['portfolio_page', 'project', 'blog_post'], 'IN')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($existingIds) && !$force) {
      return;
    }

    if (!empty($existingIds) && $force) {
      $nodes = $nodeStorage->loadMultiple($existingIds);
      $nodeStorage->delete($nodes);
    }

    $this->seedProjects();
    $this->seedBlogs();
    $this->seedPages();
  }

  /**
   * Creates or loads a taxonomy term by name and vocabulary.
   */
  protected function ensureTerm(string $vid, string $name): int {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $existing = $storage->loadByProperties(['vid' => $vid, 'name' => $name]);
    if (!empty($existing)) {
      $term = reset($existing);
      return (int) $term->id();
    }

    $term = Term::create([
      'vid' => $vid,
      'name' => $name,
    ]);
    $term->save();
    return (int) $term->id();
  }

  /**
   * Helper to create and save a Paragraph entity.
   */
  protected function createParagraph(string $type, array $fields): array {
    $p = Paragraph::create(['type' => $type] + $fields);
    $p->save();
    return [
      'target_id' => $p->id(),
      'target_revision_id' => $p->getRevisionId(),
    ];
  }

  /**
   * Helper to create a Media Gallery paragraph from an array of items.
   */
  protected function createGalleryParagraph(string $eyebrow, string $title, array $items): array {
    $mediaParagraphs = [];
    foreach ($items as $item) {
      $mediaParagraphs[] = $this->createParagraph('media_item', [
        'field_eyebrow' => $item['eyebrow'] ?? '',
        'field_title' => $item['title'] ?? '',
        'field_subtitle' => $item['caption'] ?? '',
        'field_image_url' => $item['imageUrl'] ?? '',
      ]);
    }

    return $this->createParagraph('media_gallery', [
      'field_eyebrow' => $eyebrow,
      'field_title' => $title,
      'field_media_items' => $mediaParagraphs,
    ]);
  }

  /**
   * Seeds Saad Shafique's 7 showcase projects + UI galleries.
   */
  protected function seedProjects(): void {
    $projects = [
      [
        'title' => 'Customer Information and Billing System (LESCO CIBS)',
        'slug' => 'lesco-cibs',
        'role' => 'Full-Stack Developer',
        'summary' => 'Enterprise utility platform supporting LESCO\'s Customer Information & Billing System (CIBS), serving ~7.5 million electricity consumers across eight operational circles.',
        'status' => 'launched',
        'progress' => 100,
        'milestone' => 'Serving ~7.5M consumers across 8 operational circles — billing, payments & revenue workflows',
        'featured' => TRUE,
        'accent' => 'emerald',
        'tech' => ['Java', 'PostgreSQL', 'Back-End Development', 'Database Design', 'Enterprise Integrations'],
        'repo' => '',
        'live' => '',
        'cover' => '/images/projects/cibs-commercial-overview.png',
        'body' => '<h2>Enterprise Utility Engineering at Scale</h2><p>Worked on mission-critical enterprise utility software supporting <strong>LESCO\'s Customer Information &amp; Billing System (CIBS)</strong>, serving approximately <strong>7.5 million electricity consumers</strong> across eight operational circles.</p><h3>Core Responsibilities &amp; Impact</h3><ul><li>Engineered business-critical backend workflows managing customer master records, electricity billing cycles, payment posting, and revenue reconciliation.</li><li>Designed and hardened high-reliability <strong>PostgreSQL</strong> data workflows built for large-scale commercial operations and external system integrations.</li><li>Supported end-to-end operational views spanning commercial overview analytics, meter reading verification, customer directories, and service request resolution.</li></ul>',
        'gallery' => [
          'eyebrow' => 'Product Interface Walkthrough',
          'title' => 'CIBS Operational Modules',
          'items' => [
            [
              'eyebrow' => 'Commercial Operations',
              'title' => 'Commercial Overview Dashboard',
              'caption' => 'Division-level visibility across registered customers, amount billed, payment collection, and monthly recovery efficiency.',
              'imageUrl' => '/images/projects/cibs-commercial-overview.png',
            ],
            [
              'eyebrow' => 'Revenue & Ledger',
              'title' => 'Billing & Payments Desk',
              'caption' => 'Issued bills ledger tracking billed totals, payment receipts, overdue balances, and individual bill inspection.',
              'imageUrl' => '/images/projects/cibs-billing-payments.png',
            ],
            [
              'eyebrow' => 'Field Verification',
              'title' => 'Meter Reading Register',
              'caption' => 'Consumption register flagging anomalous meter readings alongside verified and pending kWh entries.',
              'imageUrl' => '/images/projects/cibs-meter-readings.png',
            ],
            [
              'eyebrow' => 'Account Records',
              'title' => 'Customer Directory',
              'caption' => 'Searchable customer account directory segmented by division and tariff category (Domestic, Commercial, Industrial).',
              'imageUrl' => '/images/projects/cibs-customer-directory.png',
            ],
            [
              'eyebrow' => 'Consumer Support',
              'title' => 'Customer Service Requests',
              'caption' => 'Workflow tracking for billing corrections, meter inspections, due date extensions, and account updates.',
              'imageUrl' => '/images/projects/cibs-service-requests.png',
            ],
          ],
        ],
      ],
      [
        'title' => 'Crypto Sentinel — Agentic RAG Trading Intelligence',
        'slug' => 'crypto-sentinel',
        'role' => 'Backend Developer',
        'summary' => 'Real-time Crypto-Trader Agentic System with RAG combining global news sentiment, major macro events, and historical market signals dating back to 2020.',
        'status' => 'in_progress',
        'progress' => 88,
        'milestone' => 'Expanding multi-agent sentiment scoring & human-in-the-loop signal approvals',
        'featured' => TRUE,
        'accent' => 'violet',
        'tech' => ['Python', 'LangChain', 'Agentic Workflows', 'AI Agent Development', 'RAG'],
        'repo' => '',
        'live' => '',
        'cover' => '/images/projects/sentinel-ai-signals.png',
        'body' => '<h2>Autonomous Market Intelligence With Historical Memory</h2><p>Developed <strong>Crypto Sentinel</strong>, an agentic RAG-powered crypto intelligence and trading-signal system that evaluates real-time global sentiment and major market events to estimate buy, hold, or sell opportunities.</p><h3>Key Capabilities</h3><ul><li><strong>Historical RAG Memory (2020–Present):</strong> Grounds every trade signal in similar historical market setups and prior outcomes rather than reacting blindly to short-term noise.</li><li><strong>Human-in-the-Loop Signal Review:</strong> Surfaces model confidence, technical indicators (RSI, MACD, moving averages), and historical parallels with Approve / Reject / Override actions.</li><li><strong>Portfolio &amp; Narrative Tracking:</strong> Tracks personal holdings, cost basis, unrealized P/L, and categorized market news narratives in one unified workspace.</li></ul>',
        'gallery' => [
          'eyebrow' => 'Sentinel Intelligence Suite',
          'title' => 'AI Signals, Portfolio & Market Sentiment Views',
          'items' => [
            [
              'eyebrow' => 'Agentic Decision Engine',
              'title' => 'AI Signals — Human-in-the-Loop Review',
              'caption' => 'Buy/Hold/Sell recommendations backed by model confidence, technical context, and historical memory outcomes.',
              'imageUrl' => '/images/projects/sentinel-ai-signals.png',
            ],
            [
              'eyebrow' => 'Portfolio Analytics',
              'title' => 'Holdings & Unrealized P/L Tracker',
              'caption' => 'Tracks total portfolio value, unrealized profit/loss, largest allocation, and cost basis across assets.',
              'imageUrl' => '/images/projects/sentinel-portfolio.png',
            ],
            [
              'eyebrow' => 'Sentiment Pipeline',
              'title' => 'Market News & Narrative Sentiment',
              'caption' => 'Real-time market briefs scored as Positive, Neutral, or Negative to power downstream trading agents.',
              'imageUrl' => '/images/projects/sentinel-market-news.png',
            ],
            [
              'eyebrow' => 'Live Watchlist',
              'title' => 'Market Explorer',
              'caption' => 'Asset discovery with 24h deltas, market cap rankings, 7d trend sparklines, and custom watchlists.',
              'imageUrl' => '/images/projects/sentinel-markets.png',
            ],
            [
              'eyebrow' => 'Identity',
              'title' => 'Sentinel Brand System',
              'caption' => 'Sentinel — Crypto Intelligence visual identity.',
              'imageUrl' => '/images/projects/sentinel-brand.png',
            ],
          ],
        ],
      ],
      [
        'title' => 'Fuse and Relay Box OCR (FuseLab)',
        'slug' => 'fuse-relay-box-ocr',
        'role' => 'Data Engineer',
        'summary' => 'Computer-vision OCR pipeline built with OpenCV, PyTorch, and CUDA to extract printed fuse and relay ratings directly from plant-room photographs without manual transcription errors.',
        'status' => 'launched',
        'progress' => 100,
        'milestone' => 'Deployed image-assisted OCR extraction pipeline eliminating manual plant-room transcription errors',
        'featured' => TRUE,
        'accent' => 'cyan',
        'tech' => ['OpenCV', 'PyTorch', 'OCR Algorithm', 'CUDA', 'Python'],
        'repo' => '',
        'live' => '',
        'cover' => '/images/projects/fuselab-detection-result.png',
        'body' => '<h2>Eliminating Plant-Room Transcription Errors</h2><p>Asset records were previously typed up manually from phone photographs taken inside cramped electrical plant rooms. Manual transcription was slow, inconsistent across engineers, and error-prone enough that downstream asset pipelines could not be trusted.</p><h3>Engineering Solution</h3><ul><li>Built a specialized <strong>OpenCV + PyTorch + CUDA</strong> OCR model tuned for low-contrast, curved, and reflective markings printed on blade and cartridge fuses (e.g., <code>15A</code>, <code>T6.3A 250V</code>).</li><li>Paired the inference pipeline with a focused inspection workspace allowing engineers to crop target components, verify extracted current/voltage ratings, and export structured JSON straight into downstream CIBS asset records.</li></ul>',
        'gallery' => [
          'eyebrow' => 'FuseLab Inspection Workspace',
          'title' => 'End-to-End Fuse Rating Detection',
          'items' => [
            [
              'eyebrow' => 'OCR Output',
              'title' => 'Cartridge Fuse Rating Detection (6.3 A / 250 V)',
              'caption' => 'Extracts printed T6.3A 250V markings into structured current rating, voltage rating, and JSON export.',
              'imageUrl' => '/images/projects/fuselab-detection-result.png',
            ],
            [
              'eyebrow' => 'Inspection Canvas',
              'title' => 'Blade Fuse 15A Localization',
              'caption' => 'Interactive bounding region targeting printed amperage markings on automotive & industrial blade fuses.',
              'imageUrl' => '/images/projects/fuselab-blade-fuse.png',
            ],
            [
              'eyebrow' => 'Workspace View',
              'title' => 'Cartridge Fuse Reference Inspection',
              'caption' => 'Clean dark-room operator interface designed for rapid plant-room verification and history tracking.',
              'imageUrl' => '/images/projects/fuselab-cartridge-canvas.png',
            ],
          ],
        ],
      ],
      [
        'title' => 'Real-Time Object Detection & Live Fine-Tuning Studio',
        'slug' => 'realtime-object-detection',
        'role' => 'Machine Learning Operations',
        'summary' => 'End-to-end computer vision desktop application pairing real-time object detection with zero-downtime live model fine-tuning and PostgreSQL event telemetry.',
        'status' => 'in_progress',
        'progress' => 92,
        'milestone' => 'Refining zero-downtime on-the-fly class teaching & SQLAlchemy event logging',
        'featured' => TRUE,
        'accent' => 'amber',
        'tech' => ['Python', 'PyQt', 'PostgreSQL', 'SQLAlchemy', 'PyTorch'],
        'repo' => 'https://github.com/saadsh15/object_detection.git',
        'live' => '',
        'cover' => '/images/projects/project-object-detection.jpg',
        'body' => '<h2>Industrial Inspection With On-The-Fly Learning</h2><p>Designed an end-to-end computer vision desktop platform built for industrial inspection and real-time monitoring workflows.</p><h3>Key Architecture Highlights</h3><ul><li><strong>Real-Time Detection + Live Fine-Tuning:</strong> Operators can detect standard object classes, capture edge cases, and teach the model brand-new classes on the fly without shutting down the application.</li><li><strong>High-Frequency Relational Telemetry:</strong> Streams detection events and inspection metadata directly into <strong>PostgreSQL</strong> via <strong>SQLAlchemy</strong> for downstream auditing and analytics.</li><li><strong>Operator-First Desktop UI:</strong> Built with <strong>PyQt</strong> for responsive camera feeds, bounding overlays, and live training controls.</li></ul>',
      ],
      [
        'title' => 'Biometric Facial Recognition Engine (MTCNN + FaceNet + pgvector)',
        'slug' => 'facial-recognition-model',
        'role' => 'ML Engineer',
        'summary' => 'High-throughput biometric identification system combining MTCNN + FaceNet embeddings with HNSW-indexed pgvector search and a multi-threaded inference engine.',
        'status' => 'launched',
        'progress' => 100,
        'milestone' => 'Real-time multi-threaded inference with HNSW pgvector indexing — zero frame lag',
        'featured' => FALSE,
        'accent' => 'rose',
        'tech' => ['Python', 'PyTorch', 'FaceNet', 'MTCNN', 'pgvector', 'PostgreSQL'],
        'repo' => 'https://github.com/saadsh15/facial_recognition_webapp.git',
        'live' => '',
        'cover' => '/images/projects/project-facial-biometrics.jpg',
        'body' => '<h2>Zero-Lag Biometric Identification</h2><p>Engineered a custom biometric identification system built for automated check-in workflows and real-time security monitoring.</p><h3>Architecture</h3><ul><li><strong>Deep Feature Extraction:</strong> Combines <strong>MTCNN</strong> face detection/alignment with <strong>FaceNet</strong> deep convolutional embeddings.</li><li><strong>Sub-Millisecond Vector Lookup:</strong> Stores embeddings in <strong>PostgreSQL with pgvector</strong> using <strong>HNSW indexes</strong> for scalable nearest-neighbor identity matching.</li><li><strong>Decoupled Multi-Threaded Pipeline:</strong> Separates frame capture from GPU/CPU inference so video streams sustain real-time throughput without CPU saturation or frame lag.</li></ul>',
      ],
      [
        'title' => 'Utility Billing Custom Modules for ERPNext',
        'slug' => 'erpnext-utility-billing',
        'role' => 'ERPNext Developer',
        'summary' => 'Custom ERPNext modules extending the ERP platform with multi-utility billing workflows for electricity, gas, and water via clean REST APIs.',
        'status' => 'launched',
        'progress' => 100,
        'milestone' => 'Multi-utility billing engine for electricity, gas & water integrated into ERPNext',
        'featured' => FALSE,
        'accent' => 'emerald',
        'tech' => ['ERPNext', 'ERP Software', 'Custom App', 'REST API', 'Python'],
        'repo' => '',
        'live' => '',
        'cover' => '/images/projects/project-erpnext-utility.jpg',
        'body' => '<h2>Extending ERPNext Beyond Standard Accounting</h2><p>Designed and integrated custom-made modules inside <strong>ERPNext</strong> to add native multi-utility billing capabilities—supporting <strong>electricity, gas, and water</strong> metering, tariff calculations, and invoice generation.</p><h3>Why It Matters</h3><p>Gives organizations running ERPNext the flexibility to manage utility billing, meter cycles, and external REST API integrations inside a single unified ERP backbone.</p>',
      ],
      [
        'title' => 'Car Price Prediction & Retention Model (97% Accuracy)',
        'slug' => 'car-price-prediction-model',
        'role' => 'Machine Learning Developer',
        'summary' => 'Custom PyTorch regression model analyzing used-car price retention by model year and engine specification, achieving 97% accuracy on the evaluation dataset.',
        'status' => 'launched',
        'progress' => 100,
        'milestone' => 'Achieved 97% dataset accuracy modeling depreciation curves by year & engine',
        'featured' => FALSE,
        'accent' => 'cyan',
        'tech' => ['PyTorch', 'NumPy', 'Python', 'Data Analytics'],
        'repo' => '',
        'live' => '',
        'cover' => '/images/projects/project-car-valuation.jpg',
        'body' => '<h2>Data-Driven Used Vehicle Valuation</h2><p>Created a custom <strong>PyTorch</strong> neural regression model to evaluate and compare vehicle price retention across model years and engine configurations.</p><h3>Results</h3><ul><li>Feature engineering and numerical preprocessing with <strong>NumPy</strong> and Python data analytics pipelines.</li><li>Outputs estimated used-car market valuations with <strong>97% accuracy</strong> on the benchmark dataset, making depreciation patterns easy to compare across models.</li></ul>',
      ],
    ];

    foreach ($projects as $item) {
      $techIds = array_map(fn(string $t) => ['target_id' => $this->ensureTerm('tech_stack', $t)], $item['tech']);
      $sections = [];
      if (!empty($item['gallery'])) {
        $sections[] = $this->createGalleryParagraph(
          $item['gallery']['eyebrow'],
          $item['gallery']['title'],
          $item['gallery']['items']
        );
      }

      Node::create([
        'type' => 'project',
        'title' => $item['title'],
        'status' => 1,
        'field_slug' => $item['slug'],
        'field_role' => $item['role'],
        'field_summary' => $item['summary'],
        'field_status' => $item['status'],
        'field_progress' => $item['progress'],
        'field_current_milestone' => $item['milestone'],
        'field_featured' => $item['featured'],
        'field_accent' => $item['accent'],
        'field_tech_stack' => $techIds,
        'field_repo_url' => $item['repo'],
        'field_live_url' => $item['live'],
        'field_cover_url' => $item['cover'],
        'field_body' => [
          'value' => $item['body'],
          'format' => 'full_html',
        ],
        'field_sections' => $sections,
      ])->save();
    }
  }

  /**
   * Seeds technical blog posts aligned with Saad Shafique's engineering domains.
   */
  protected function seedBlogs(): void {
    $blogs = [
      [
        'title' => 'Scaling Utility Billing Workflows: Lessons From Serving 7.5M+ Consumers on PostgreSQL',
        'slug' => 'scaling-utility-billing-postgresql',
        'summary' => 'Architectural patterns for database reliability, billing ledger integrity, and cross-circle operational reporting in high-volume utility systems.',
        'category' => 'Enterprise Systems',
        'tags' => ['PostgreSQL', 'Java', 'Database Design', 'CIBS'],
        'reading_time' => 6,
        'featured' => TRUE,
        'date' => '2026-11-18',
        'body' => '<h2>When Billing Reliability Is Non-Negotiable</h2><p>Working on enterprise utility software serving millions of electricity consumers teaches a specific discipline: schema integrity, transactional boundaries, and predictable query plans matter far more than trendy abstractions.</p><h3>Key Engineering Principles</h3><ul><li><strong>Immutable Billing &amp; Payment Ledgers:</strong> Model adjustments and reversals explicitly instead of mutating historical bills.</li><li><strong>Division-Aware Indexing:</strong> Align composite PostgreSQL indexes with real commercial access patterns—reference numbers, billing cycles, and circle/division filters.</li><li><strong>External Integration Isolation:</strong> Decouple bank payment gateways and meter-reading ingestion from core billing runs via idempotent queues.</li></ul>',
      ],
      [
        'title' => 'Eliminating Frame Lag in Real-Time Biometric Recognition With pgvector HNSW',
        'slug' => 'realtime-biometrics-pgvector-hnsw',
        'summary' => 'How decoupling frame capture from MTCNN + FaceNet inference and indexing embeddings with pgvector HNSW unlocks smooth real-time recognition.',
        'category' => 'Computer Vision',
        'tags' => ['PyTorch', 'FaceNet', 'pgvector', 'PostgreSQL', 'MLOps'],
        'reading_time' => 5,
        'featured' => TRUE,
        'date' => '2026-11-11',
        'body' => '<h2>Why Naive Video Inference Stalls</h2><p>Running face detection, alignment, embedding generation, and linear database scans synchronously on a single camera thread guarantees dropped frames and CPU saturation.</p><h3>The Decoupled Multi-Threaded Approach</h3><ol><li><strong>Capture Thread:</strong> Keeps the latest frame hot without blocking on inference.</li><li><strong>Inference Worker:</strong> Runs MTCNN + FaceNet embeddings asynchronously on batched crops.</li><li><strong>HNSW Vector Search in PostgreSQL:</strong> Uses <code>pgvector</code> HNSW indexing so identity lookup stays sub-millisecond even as registered identities scale.</li></ol>',
      ],
      [
        'title' => 'Designing Explainable Human-in-the-Loop RAG Pipelines for Real-Time Market Signals',
        'slug' => 'explainable-rag-market-signals',
        'summary' => 'How combining historical market memory (2020–present) with live sentiment feeds and operator review controls makes autonomous agents trustworthy.',
        'category' => 'Applied AI',
        'tags' => ['LangChain', 'RAG', 'Agentic Workflows', 'Python'],
        'reading_time' => 5,
        'featured' => FALSE,
        'date' => '2026-11-04',
        'body' => '<h2>Why Black-Box Trading Bots Fail</h2><p>Autonomous financial agents that emit bare BUY/SELL signals without historical grounding or reasoning traces are impossible to trust in volatile markets.</p><h3>Grounding Signals in Multi-Year Memory</h3><ul><li><strong>Historical Retrieval (2020–Present):</strong> Retrieve structurally similar past market regimes alongside their actual outcomes before scoring a live signal.</li><li><strong>Multi-Signal Context:</strong> Combine live news narrative sentiment with deterministic technical indicators (RSI, MACD, moving averages).</li><li><strong>Human-in-the-Loop Controls:</strong> Surface confidence scores, historical parallels, and Approve / Reject / Override actions so judgment stays with the operator.</li></ul>',
      ],
    ];

    foreach ($blogs as $post) {
      $catId = $this->ensureTerm('blog_category', $post['category']);
      $tagIds = array_map(fn(string $t) => ['target_id' => $this->ensureTerm('blog_tag', $t)], $post['tags']);

      Node::create([
        'type' => 'blog_post',
        'title' => $post['title'],
        'status' => 1,
        'field_slug' => $post['slug'],
        'field_summary' => $post['summary'],
        'field_category' => ['target_id' => $catId],
        'field_tags' => $tagIds,
        'field_reading_time' => $post['reading_time'],
        'field_featured' => $post['featured'],
        'field_published_date' => $post['date'],
        'field_body' => [
          'value' => $post['body'],
          'format' => 'full_html',
        ],
      ])->save();
    }
  }

  /**
   * Seeds Modular Pages (Home & About) reflecting Saad Shafique's positioning.
   */
  protected function seedPages(): void {
    // --- HOME PAGE ---
    $hero = $this->createParagraph('hero_section', [
      'field_eyebrow' => 'Saad Shafique • Full-Stack, Backend & Applied AI Engineer',
      'field_title' => 'Engineering enterprise-scale systems &',
      'field_highlight_text' => 'real-time AI products.',
      'field_subtitle' => 'From mission-critical utility billing platforms serving 7.5M+ consumers and custom ERPNext apps to PyTorch computer vision pipelines, pgvector biometrics, and agentic RAG trading systems.',
      'field_cta_primary_label' => 'Explore 7 Featured Projects',
      'field_cta_primary_url' => '/projects',
      'field_cta_secondary_label' => 'Book a Meeting',
      'field_cta_secondary_url' => 'https://calendly.com/shafiquesaad15/new-meeting',
      'field_variant' => 'spotlight',
      'field_portrait_url' => '/images/saad-portrait.jpg',
      'field_card_name' => 'Saad Shafique',
      'field_card_tagline' => 'Full-Stack · Backend · Applied AI',
      'field_card_quote' => 'Reliable software isn’t measured by how clever it looks on a whiteboard—it’s measured by how quietly it holds under real load, long after launch.',
      'field_card_caption' => 'Disciplined architecture, trustworthy data, and the craft to ship systems people can depend on every day.',
      'field_bg_type' => 'image',
      'field_bg_media_url' => '/images/backgrounds/hero-architectural.jpg',
      'field_bg_overlay' => 'editorial',
    ]);

    $stats = $this->createParagraph('stats_strip', [
      'field_title' => '',
      'field_stat_items' => [
        $this->createParagraph('stat_item', [
          'field_metric' => '7.5M+',
          'field_title' => 'Utility Consumers',
          'field_subtitle' => 'LESCO CIBS across 8 circles',
        ]),
        $this->createParagraph('stat_item', [
          'field_metric' => '97%',
          'field_title' => 'PyTorch Accuracy',
          'field_subtitle' => 'Custom ML valuation model',
        ]),
        $this->createParagraph('stat_item', [
          'field_metric' => '07',
          'field_title' => 'Shipped & Active Systems',
          'field_subtitle' => 'Enterprise, Vision & Agentic AI',
        ]),
        $this->createParagraph('stat_item', [
          'field_metric' => '2020+',
          'field_title' => 'Historical RAG Memory',
          'field_subtitle' => 'Crypto Sentinel market signals',
        ]),
      ],
    ]);

    $featuredShowcase = $this->createParagraph('showcase_block', [
      'field_eyebrow' => 'Selected Engineering Showcase',
      'field_title' => 'Enterprise Platforms, Computer Vision & Agentic AI',
      'field_subtitle' => 'Flagship builds across utility billing, real-time OCR, industrial object detection, and RAG-powered trading intelligence.',
      'field_source' => 'projects',
      'field_filter' => 'featured',
      'field_limit' => 4,
      'field_link_url' => '/projects',
      'field_link_label' => 'View all 7 projects',
    ]);

    $bento = $this->createParagraph('bento_grid', [
      'field_eyebrow' => 'Positioning & Core Capabilities',
      'field_title' => 'Bridging mission-critical backends with applied machine learning.',
      'field_subtitle' => 'End-to-end systems engineering across enterprise utility operations, production computer vision, and intelligent automation.',
      'field_layout' => 'asymmetric',
      'field_bento_items' => [
        $this->createParagraph('bento_item', [
          'field_eyebrow' => 'Enterprise & ERP Systems',
          'field_title' => 'High-Reliability Backend & Utility Scale',
          'field_subtitle' => 'Hands-on experience on LESCO CIBS (~7.5M electricity consumers) and custom ERPNext multi-utility billing modules (electricity, gas, water) with Java, PostgreSQL, and REST APIs.',
          'field_metric' => '7.5M+ Scale',
          'field_icon' => 'layers',
          'field_span' => '2x1',
          'field_accent' => 'emerald',
          'field_link_url' => '/projects/lesco-cibs',
          'field_link_label' => 'Explore CIBS case study',
          'field_image_url' => '/images/projects/project-erpnext-utility.jpg',
        ]),
        $this->createParagraph('bento_item', [
          'field_eyebrow' => 'Computer Vision & MLOps',
          'field_title' => 'PyTorch, OpenCV & CUDA Pipelines',
          'field_subtitle' => 'Plant-room Fuse/Relay OCR, live fine-tuning object detection in PyQt, and multi-threaded MTCNN + FaceNet biometrics backed by pgvector HNSW.',
          'field_metric' => 'Real-Time',
          'field_icon' => 'cpu',
          'field_span' => '1x1',
          'field_accent' => 'cyan',
          'field_link_url' => '/projects/fuse-relay-box-ocr',
          'field_link_label' => 'Inspect FuseLab OCR',
          'field_image_url' => '/images/projects/project-facial-biometrics.jpg',
        ]),
        $this->createParagraph('bento_item', [
          'field_eyebrow' => 'Agentic AI & RAG',
          'field_title' => 'LangChain Signal Intelligence',
          'field_subtitle' => 'Autonomous market-sentiment and RAG trading pipelines combining real-time global news with multi-year historical market context since 2020.',
          'field_metric' => 'RAG + Agents',
          'field_icon' => 'sparkles',
          'field_span' => '1x1',
          'field_accent' => 'violet',
          'field_link_url' => '/projects/crypto-sentinel',
          'field_link_label' => 'View Crypto Sentinel',
          'field_image_url' => '/images/projects/sentinel-markets.png',
        ]),
        $this->createParagraph('bento_item', [
          'field_eyebrow' => 'Full-Stack & Systems Architecture',
          'field_title' => 'Production APIs, Data Pipelines & Interfaces',
          'field_subtitle' => 'Designing maintainable service boundaries, PostgreSQL schemas, containerized deployments, and clean operational UIs built for daily real-world use.',
          'field_metric' => 'End-to-End',
          'field_icon' => 'blocks',
          'field_span' => '2x1',
          'field_accent' => 'amber',
          'field_link_url' => '/projects',
          'field_link_label' => 'Browse all systems',
          'field_image_url' => '/images/backgrounds/section-monolith.jpg',
        ]),
      ],
    ]);

    $productSnapshots = $this->createGalleryParagraph(
      'Visual Product Snapshots',
      'Inside the Interfaces I Build',
      [
        [
          'eyebrow' => 'LESCO CIBS • Enterprise Utility',
          'title' => 'Commercial Operations & Collections',
          'caption' => 'Real-time commercial overview tracking customer accounts, billing totals, and recovery efficiency across divisions.',
          'imageUrl' => '/images/projects/cibs-commercial-overview.png',
        ],
        [
          'eyebrow' => 'Crypto Sentinel • Agentic RAG',
          'title' => 'Human-in-the-Loop AI Trading Signals',
          'caption' => 'Confidence-scored BUY/HOLD/SELL signals grounded in technical indicators and 2020–present historical memory.',
          'imageUrl' => '/images/projects/sentinel-ai-signals.png',
        ],
        [
          'eyebrow' => 'FuseLab • OpenCV + PyTorch OCR',
          'title' => 'Automated Fuse Rating Extraction (6.3 A)',
          'caption' => 'Extracts printed cartridge and blade fuse ratings from plant-room imagery straight into structured JSON.',
          'imageUrl' => '/images/projects/fuselab-detection-result.png',
        ],
      ]
    );

    $blogShowcase = $this->createParagraph('showcase_block', [
      'field_eyebrow' => 'Engineering Writing & Build Logs',
      'field_title' => 'Latest Technical Notes',
      'field_subtitle' => 'Field notes on PostgreSQL at utility scale, real-time computer vision, and modular systems architecture.',
      'field_source' => 'blogs',
      'field_filter' => 'all',
      'field_limit' => 3,
      'field_link_url' => '/blog',
      'field_link_label' => 'Read all articles',
    ]);

    $architecturalBanner = $this->createParagraph('background_banner', [
      'field_eyebrow' => 'Engineering Ethos · Systems Built To Last',
      'field_title' => 'Where high-reliability backend infrastructure meets applied machine intelligence.',
      'field_subtitle' => 'From enterprise utility billing ledgers serving millions of accounts to low-latency PyTorch vision pipelines and explainable RAG agents.',
      'field_cta_primary_label' => 'Explore All Projects',
      'field_cta_primary_url' => '/projects',
      'field_cta_secondary_label' => 'About & Background',
      'field_cta_secondary_url' => '/about',
      'field_height' => 'medium',
      'field_bg_type' => 'video',
      'field_bg_media_url' => '/videos/ambient-monolith.mp4',
      'field_bg_poster_url' => '/images/backgrounds/section-monolith.jpg',
      'field_bg_overlay' => 'editorial',
    ]);

    $cta = $this->createParagraph('cta_banner', [
      'field_eyebrow' => 'Let\'s Build Something Substantial',
      'field_title' => 'Looking for a Full-Stack, Backend, or Applied AI Engineer?',
      'field_subtitle' => 'Connect on LinkedIn to discuss engineering roles, enterprise backend systems, or applied AI collaborations, or browse my open-source work on GitHub.',
      'field_cta_primary_label' => 'Book a Meeting',
      'field_cta_primary_url' => 'https://calendly.com/shafiquesaad15/new-meeting',
      'field_cta_secondary_label' => 'Connect on LinkedIn',
      'field_cta_secondary_url' => 'https://www.linkedin.com/in/saad-shafique-60115934b/',
      'field_accent' => 'cyan',
      'field_bg_type' => 'ambient_canvas',
      'field_bg_overlay' => 'subtle',
    ]);

    Node::create([
      'type' => 'portfolio_page',
      'title' => 'Home',
      'status' => 1,
      'field_slug' => 'home',
      'field_meta_description' => 'Saad Shafique — Full-Stack, Backend & Applied AI Engineer. Enterprise utility systems, ERPNext, PyTorch computer vision, and Agentic RAG.',
      'field_show_in_nav' => FALSE,
      'field_nav_order' => 0,
      'field_sections' => [
        $hero,
        $stats,
        $featuredShowcase,
        $architecturalBanner,
        $bento,
        $productSnapshots,
        $blogShowcase,
        $cta,
      ],
    ])->save();

    // --- ABOUT PAGE ---
    $aboutHero = $this->createParagraph('hero_section', [
      'field_eyebrow' => 'About Saad Shafique',
      'field_title' => 'Full-stack reliability meets',
      'field_highlight_text' => 'applied AI execution.',
      'field_subtitle' => 'I build business-critical enterprise backends, ERP integrations, and production machine learning & computer vision systems that solve real operational bottlenecks.',
      'field_cta_primary_label' => 'View All Projects',
      'field_cta_primary_url' => '/projects',
      'field_cta_secondary_label' => 'LinkedIn Profile',
      'field_cta_secondary_url' => 'https://www.linkedin.com/in/saad-shafique-60115934b/',
      'field_variant' => 'minimal',
      'field_portrait_url' => '/images/saad-portrait.jpg',
      'field_card_name' => 'Saad Shafique',
    ]);

    $aboutText = $this->createParagraph('rich_text_block', [
      'field_eyebrow' => 'Background & Engineering Focus',
      'field_title' => 'What I Focus On',
      'field_width' => 'medium',
      'field_body' => [
        'value' => '<p>My work sits at the intersection of <strong>enterprise backend engineering</strong> and <strong>applied artificial intelligence</strong>. Whether hardening PostgreSQL billing workflows for millions of electricity consumers or shipping CUDA-accelerated OCR and real-time biometric models, I care deeply about reliability, clean data pipelines, and usable interfaces.</p><h3>Core Technical Pillars</h3><ul><li><strong>Enterprise Backend &amp; ERP Systems:</strong> Java, Python, PostgreSQL database design, LESCO CIBS (~7.5M consumers), and custom ERPNext multi-utility billing apps with REST APIs.</li><li><strong>Computer Vision &amp; MLOps:</strong> PyTorch, OpenCV, CUDA, MTCNN + FaceNet, pgvector HNSW indexing, and PyQt industrial inspection tools with zero-downtime live fine-tuning.</li><li><strong>Agentic AI &amp; RAG:</strong> LangChain agentic workflows combining real-time sentiment feeds with multi-year historical memory for explainable, human-in-the-loop decision support.</li></ul>',
        'format' => 'full_html',
      ],
    ]);

    $aboutCta = $this->createParagraph('cta_banner', [
      'field_eyebrow' => 'Get In Touch',
      'field_title' => 'Interested in working together?',
      'field_subtitle' => 'Reach out on LinkedIn or inspect my computer vision and backend repositories on GitHub.',
      'field_cta_primary_label' => 'Book a Meeting',
      'field_cta_primary_url' => 'https://calendly.com/shafiquesaad15/new-meeting',
      'field_cta_secondary_label' => 'Connect on LinkedIn',
      'field_cta_secondary_url' => 'https://www.linkedin.com/in/saad-shafique-60115934b/',
      'field_accent' => 'violet',
      'field_bg_type' => 'ambient_canvas',
      'field_bg_overlay' => 'subtle',
    ]);

    Node::create([
      'type' => 'portfolio_page',
      'title' => 'About',
      'status' => 1,
      'field_slug' => 'about',
      'field_meta_description' => 'About Saad Shafique — Full-Stack, Backend & Applied AI Engineer.',
      'field_show_in_nav' => TRUE,
      'field_nav_order' => 1,
      'field_sections' => [
        $aboutHero,
        $aboutText,
        $aboutCta,
      ],
    ])->save();
  }

}
