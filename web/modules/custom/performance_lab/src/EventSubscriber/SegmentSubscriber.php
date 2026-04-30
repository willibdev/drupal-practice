<?php

declare(strict_types=1);

namespace Drupal\performance_lab\EventSubscriber;

use Drupal\segment_analytics\SegmentAnalyticsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber for segment events.
 */
class SegmentSubscriber implements EventSubscriberInterface {

  /**
   * Request stack that controls the lifecycle of requests.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Custom service for Segment integratios.
   *
   * @var \Drupal\segment_analytics\SegmentAnalyticsService
   */
  protected $segmentService;

  /**
   * Event Subscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack that controls the lifecycle of requests.
   * @param \Drupal\segment_analytics\SegmentAnalyticsService $segment_service
   *   Custom service for Segment integratios.
   */
  public function __construct(RequestStack $request_stack, SegmentAnalyticsService $segment_service) {
    $this->requestStack = $request_stack;
    $this->segmentService = $segment_service;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponse', 0],
    ];
  }

  /**
   * Trigger Segment event when the page has loaded.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Event subscriber object.
   */
  public function onResponse(ResponseEvent $event): void {
    // Avoid subrequests.
    if (!$event->isMainRequest()) {
      return;
    }

    // Only for HTML (Avoid APIs, AJX, etc.).
    if ($event->getResponse()->headers->get('Content-Type') !== 'text/html; charset=UTF-8') {
      return;
    }

    // The identity must be sent only one when the user is registered.
    // $this->segmentService->identity();
    // Segment events.
    // $this->segmentService->page('Página principal', 'General');
    // $this->segmentService->track('Page load event', [
    //   'Test' => 'Page loading...',
    // ]);
  }

}
