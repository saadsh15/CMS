<?php

namespace Drupal\portfolio_core\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Pushes on-demand revalidation webhooks to Next.js when Drupal content changes.
 */
class RevalidationNotifier {

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Sends a lightweight POST request to Next.js /api/revalidate.
   */
  public function notify(array $tags = ['cms']): void {
    $nextUrl = getenv('NEXT_INTERNAL_URL') ?: 'http://web:3000';
    $secret = getenv('REVALIDATE_SECRET') ?: '';

    if (empty($nextUrl) || empty($secret)) {
      return;
    }

    try {
      $this->httpClient->request('POST', rtrim($nextUrl, '/') . '/api/revalidate', [
        'timeout' => 2.0,
        'connect_timeout' => 1.0,
        'headers' => [
          'Content-Type' => 'application/json',
          'x-revalidate-secret' => $secret,
        ],
        'json' => [
          'tags' => array_values(array_unique($tags)),
          'timestamp' => time(),
        ],
      ]);
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('portfolio_core')->debug(
        'Skipped Next.js revalidation webhook: @message',
        ['@message' => $e->getMessage()]
      );
    }
  }

}
