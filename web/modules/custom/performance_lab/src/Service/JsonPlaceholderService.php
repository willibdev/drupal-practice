<?php

declare(strict_types=1);

namespace Drupal\performance_lab\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\http_client_manager\HttpClientInterface;

/**
 * Service for lazy builder content.
 */
class JsonPlaceholderService {

  use StringTranslationTrait;

  /**
   * Circuite breaker configuration.
   */
  private const int FAILURE_THRESHOLD = 3;
  private const int MAX_RETRIES = 2;
  private const int CIRCUIT_TTL = 60;
  private const string CIRCUIT_KEY = 'posts_circuit';

  /**
   * Defines an account interface which represents the current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The http client.
   *
   * @var \Drupal\http_client_manager\HttpClientInterface
   */
  protected HttpClientInterface $httpClient;

  /**
   * Logger channel interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $loggerChannel;

  /**
   * Defines an interface for cache implementations.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructor for the Lazy Builder Service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Defines an account interface which represents the current user.
   * @param \Drupal\http_client_manager\HttpClientInterface $http_client
   *   The HTTP Client Manager Factory service.
   * @param \Drupal\Core\Logger\LoggerChannel $logger_channel
   *   Logger channel interface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Defines an interface for cache implementations.
   */
  public function __construct(AccountProxyInterface $current_user, HttpClientInterface $http_client, LoggerChannel $logger_channel, CacheBackendInterface $cache_backend) {
    $this->currentUser = $current_user;
    $this->httpClient = $http_client;
    $this->loggerChannel = $logger_channel;
    $this->cache = $cache_backend;
  }

  /**
   * Lazy builder entry point.
   *
   * This method is called by Drupal BigPipe to render dynamic content.
   */
  #[TrustedCallback]
  public function buildPosts(): array {
    $cachedApiResponseID = 'posts:' . $this->currentUser->id();

    // Check api response in cache.
    $cachedApiResponse = $this->cache->get($cachedApiResponseID);

    if ($cachedApiResponse) {
      return $this->buildRender($cachedApiResponse->data);
    }

    // Check circuit breaker state.
    $circuit = $this->cache->get(self::CIRCUIT_KEY);

    if ($circuit && $circuit->data['state'] == 'OPEN') {
      // Circuit is open -> return fallback inmmediately.
      return $this->buildFallback('Circuit is open');
    }

    try {

      $data = $this->callApiWithRetry();

      // Store API resonse in cache.
      $this->cache->set(
        $cachedApiResponseID,
        $data,
        time() + 300
      );

      // Reset circuit on success.
      $this->resetCircuit();

      return $this->buildRender($data);
    }
    catch (\Exception $e) {
      // Register failure.
      $this->registerFailure();

      // Log error for observability.
      $this->loggerChannel->error('Posts API failed: @message', [
        '@message' => $e->getMessage(),
      ]);

      return $this->buildFallback('API failure');
    }
  }

  /**
   * Calls external API with retry + exponential backoff.
   */
  private function callApiWithRetry(): array {
    $attempt = 0;

    while ($attempt <= self::MAX_RETRIES) {
      try {
        // Make HTTP Request.
        $posts = $this->httpClient->getPosts();
        return $posts->toArray();
      }
      catch (\Exception $e) {
        $attempt++;

        // If max retries reached -> throw exception.
        if ($attempt > self::MAX_RETRIES) {
          throw $e;
        }

        // Exponential backoff with jitter.
        $delay = pow(2, $attempt) + rand(0, 100 / 100);

        // Sleep to avoid hammering the API.
        usleep((int) ($delay * 1000000));
      }
    }

    return [];
  }

  /**
   * Registers a faolure and opens the circuit if threshold is reached.
   */
  private function registerFailure(): void {
    $data = $this->cache->get(self::CIRCUIT_KEY);

    $failures = $data ? $data->data['failures'] : 0;
    $failures++;

    // If failure threshold exceeded -> open circuit, otherwise, closed circuit.
    // Keep circuit closed but track failures.
    $this->cache->set(self::CIRCUIT_KEY, [
      'state' => $failures >= self::FAILURE_THRESHOLD ? 'OPEN' : 'CLOSED',
      'failures' => $failures,
    ], time() + self::CIRCUIT_TTL);
  }

  /**
   * Reset circuit to CLOSED state after a successful request.
   */
  private function resetCircuit(): void {
    $this->cache->delete(self::CIRCUIT_KEY);
  }

  /**
   * Build the final render array.
   *
   * @param array $data
   *   API response to render.
   */
  private function buildRender(array $data): array {
    return [
      '#theme' => 'posts_list',
      '#items' => $data,
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Build fallback UI when API is unavailable.
   *
   * @param string $reason
   *   Circuit status.
   */
  private function buildFallback(string $reason): array {
    return [
      '#markup' => $this->t('No posts available right now.'),
      '#cache' => [
        'contexts' => ['user'],
        // Retry soon.
        'max-age' => 60,
      ],
    ];
  }

}
