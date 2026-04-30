<?php

declare(strict_types=1);

namespace Drupal\segment_analytics;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Session\AccountInterface;
use Segment\Segment as Analytics;
use Segment\SegmentException;

/**
 * Custom service for Segment integratios.
 */
class SegmentAnalyticsService {

  /**
   * Defines an account interface which represents the current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Logger channel factory interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $segmentLoggerChannel;

  /**
   * Constructor for custom segment tracking integration.
   *
   * @param \Drupal\Core\Session\AccountInterface $account_interface
   *   Defines an account interface which represents the current user.
   * @param \Drupal\Core\Logger\LoggerChannel $segment_logger_channel
   *   Provides an interface for entity type managers.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the interface for a configuration object factory.
   */
  public function __construct(AccountInterface $account_interface, LoggerChannel $segment_logger_channel, ConfigFactoryInterface $config_factory) {
    $this->account = $account_interface;
    $this->segmentLoggerChannel = $segment_logger_channel;

    $settings_form = $config_factory->get('segment_analytics.settings');
    Analytics::init($settings_form->get('write_key'));
  }

  /**
   * Identify the segment user.
   *
   * Identify calls let you tie a user to their actions, and recod traits about
   * them. It includes a unique User ID and any optional traits you know about
   * them.
   *
   * Segment recommends calling identity a single time when user's account is
   * first created, and only identify again later when their traits change.
   *
   * @param array $traits
   *   A hash of traits you know about the user. Things like: email, name or
   *   friends.
   * @param bool $isAnonymous
   *   Indicate whether the identity should be sent as an anonymous user.
   */
  public function identity(array $traits = [], bool $isAnonymous = FALSE): void {
    try {
      Analytics::identify([
        'userId' => $isAnonymous ? 'anonymousId' : $this->account->id(),
        'traits' => [
          'email' => $this->account->getEmail(),
          'name' => $this->account->getDisplayName(),
          ...$traits,
        ],
      ]);
    }
    catch (SegmentException $th) {
      $this->segmentLoggerChannel->error($th->getMessage(), [
        'code' => $th->getCode(),
        'line' => $th->getLine(),
      ]);
    }
  }

  /**
   * Track lets you record the actions your users perform.
   *
   * Every action triggers what Segment calls an "event", which can also have
   * associated properties.
   *
   * You'll want to track events that are indicators of success for your site,
   * like Signed Up, Item Purchased or Article Bookmarked.
   *
   * @param string $event
   *   The name of the event you're tracking. Segment recommends human-readable
   *   names like Song Played or Status Updated.
   * @param array $properties
   *   A hash of properties for the event. If the event was Product Added to
   *   cart, it might have properties like price or product.
   * @param bool $isAnonymous
   *   Indicate whether the identity should be sent as an anonymous user.
   */
  public function track(string $event, array $properties = [], bool $isAnonymous = FALSE): void {
    try {
      Analytics::track([
        'userId' => $isAnonymous ? 'anonymousId' : $this->account->id(),
        'event' => $event,
        'properties' => $properties,
      ]);
    }
    catch (SegmentException $th) {
      $this->segmentLoggerChannel->error($th->getMessage(), [
        'code' => $th->getCode(),
        'line' => $th->getLine(),
      ]);
    }
  }

  /**
   * Record page views.
   *
   * This method lets you record page views on your website, along with optional
   * extra information about the page being viewed.
   *
   * @param string $name
   *   The name of the page, for example Signup or Home.
   * @param string $category
   *   The category of the page. Useful for industries, like ecommerce, where
   *   many pages might live under a larger category. Note: if you only pass one
   *   string to page Segment assumes it's a name, not a category.
   *   You must include a name if you want to send a category.
   * @param array $properties
   *   A dictionary of properties of the page. segment automatically sends the
   *   url, title, referrer and path, but you can add your own too.
   * @param bool $isAnonymous
   *   Indicate whether the identity should be sent as an anonymous user.
   */
  public function page(string $name, string $category, array $properties = [], bool $isAnonymous = FALSE): void {
    try {
      // @todo the url must be sent as a property.
      Analytics::page([
        'userId' => $isAnonymous ? 'anonymousId' : $this->account->id(),
        'category' => $category,
        'name' => $name,
        'properties' => $properties,
      ]);
    }
    catch (SegmentException $th) {
      $this->segmentLoggerChannel->error($th->getMessage(), [
        'code' => $th->getCode(),
        'line' => $th->getLine(),
      ]);
    }
  }

  /**
   * Flush the data sent to Segment's servers.
   *
   * Flush explicitly tells the PHP runtime to flush the data sent to Segment's
   * servers. In most configurations, this is done automatically by the runtime,
   * but some PHP installations won't take care of it for you, so it's worth
   * calling at the end of your script, just to be safe.
   */
  public function flush() {
    Analytics::flush();
  }

}
