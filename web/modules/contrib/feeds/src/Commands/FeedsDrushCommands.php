<?php

namespace Drupal\feeds\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\feeds\FeedInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Defines Drush commands for the Feeds module.
 */
class FeedsDrushCommands extends DrushCommands {

  /**
   * Display all feeds using a drush command.
   *
   * @param string $feed_type
   *   The name of the feed type whose instances will be listed. Optional.
   * @param array $options
   *   A list of options for this command. See below.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Tabular data, that can be processed by drush.
   *
   * @command feeds:list-feeds
   * @aliases feeds-lf
   * @field-labels
   *   feed_type: Feed type
   *   fid: Feed ID
   *   title: Title
   *   imported: Last imported
   *   next: Next import
   *   source: Feed source
   *   item_count: Item count
   *   state: Status
   * @option limit
   *   Limit the number of feeds to show in the list. Optional.
   * @option enabled
   *   Show only enabled feeds.
   * @option disabled
   *   Show only disabled feeds.
   * @usage feeds:list-feeds
   * @usage feeds:list-feeds my_feed_type
   * @usage feeds:list-feeds --limit=10
   * @usage feeds:list-feeds --limit=10 my_feed_type
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function listFeeds($feed_type = '', array $options = [
    'limit' => 0,
    'enabled' => FALSE,
    'disabled' => FALSE,
    'format' => 'table',
  ]) {
    $entityQuery = \Drupal::entityQuery('feeds_feed');
    if (!empty($feed_type)) {
      $entityQuery->condition('type', $feed_type);
    }
    if ($options['enabled']) {
      $entityQuery->condition('status', TRUE);
    }
    elseif ($options['disabled']) {
      $entityQuery->condition('status', FALSE);
    }
    if ($options['limit'] > 0) {
      $entityQuery->range(0, $options['limit']);
    }
    $feeds = \Drupal::entityTypeManager()
      ->getStorage('feeds_feed')
      ->loadMultiple($entityQuery->execute());

    // Loop through all retrieved feed entities and prepare them for display in
    // the formatted table.
    $tableData = [];
    /** @var \Drupal\feeds\FeedInterface $feed */
    foreach ($feeds as $feed) {
      $tableData[$feed->id()] = [
        'feed_type' => $feed->bundle(),
        'fid' => $feed->id(),
        'title' => $feed->label(),
        'imported' => $feed->getImportedTime() ? date('Y-m-d\TH:i:s', $feed->getImportedTime()) : $this->t('Never'),
        'next' => ($feed->getNextImportTime() > 0) ? date('Y-m-d\TH:i:s', $feed->getNextImportTime()) : $this->t('Not scheduled'),
        'source' => $feed->getSource(),
        'item_count' => $feed->getItemCount(),
        'state' => $feed->isActive() ? $this->t('Enabled') : $this->t('Disabled'),
      ];
    }

    // Render $tableData in a renderable table for drush.
    return new RowsOfFields($tableData);
  }

  /**
   * Enable a feed specified by its id.
   *
   * @param int $fid
   *   The id of the feed which should get enabled.
   *
   * @command feeds:enable
   * @aliases feeds-en
   * @usage feeds:enable 1
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case the feed could not be loaded.
   */
  public function enableFeed($fid = NULL) {
    if (empty($fid)) {
      throw new \Exception($this->t('Please specify the ID of the feed you want to enable.'));
    }
    $feed = $this->getFeed($fid);

    // Check if the feed we got is valid.
    if ($feed instanceof FeedInterface) {
      if ($feed->isActive()) {
        $this->logger()->notice($this->t('This feed is already enabled.'));
        return;
      }
      if (!$this->io()->confirm($this->t('The following feed will be enabled: ":label" (id :id)', [':label' => $feed->label(), ':id' => $fid]))) {
        throw new UserAbortException();
      }

      $feed->setActive(TRUE);
      $feed->save();
      $this->logger->success($this->t('The feed ":label" has been enabled.', [':label' => $feed->label()]));
    }
    else {
      throw new \Exception($this->t('There is no feed with id :id', [':id' => $fid]));
    }
  }

  /**
   * Disable a feed specified by its id.
   *
   * @param int $fid
   *   The id of the feed which should get disabled.
   *
   * @command feeds:disable
   * @aliases feeds-dis
   * @usage feeds:disable 1
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case the feed could not be loaded.
   */
  public function disableFeed($fid = NULL) {
    if (empty($fid)) {
      throw new \Exception($this->t('Please specify the ID of the feed you want to disable.'));
    }
    $feed = $this->getFeed($fid);

    // Check if the feed we got is valid.
    if ($feed instanceof FeedInterface) {
      if (!$feed->isActive()) {
        $this->logger()->notice($this->t('This feed is already disabled.'));
        return;
      }
      if (!$this->io()->confirm($this->t('The following feed will be disabled: ":label" (id :id)', [':label' => $feed->label(), ':id' => $fid]))) {
        throw new UserAbortException();
      }

      $feed->setActive(FALSE);
      $feed->save();
      $this->logger->success($this->t('The feed ":label" has been disabled.', [':label' => $feed->label()]));
    }
    else {
      throw new \Exception($this->t('There is no feed with id :id', [':id' => $fid]));
    }
  }

  /**
   * Import a feed specified by its id.
   *
   * @param int $fid
   *   The id of the feed which should get imported.
   * @param array $options
   *   A list of options for this command. See below.
   *
   * @command feeds:import
   * @aliases feeds-im
   * @option import-disabled
   *   Also import feed if it is not active.
   * @usage feeds:import 1
   *
   * @throws \Exception
   *   In case something went wrong when importing the feed.
   */
  public function importFeed($fid = NULL, array $options = ['import-disabled' => FALSE]) {
    if (empty($fid)) {
      throw new \Exception($this->t('Please specify the ID of the feed you want to import.'));
    }
    $feed = $this->getFeed($fid);

    // Check if the feed we got is valid.
    if ($feed instanceof FeedInterface) {
      // Only import feed if it is either active, or the user specifically wants
      // to import the feed regardless of its active state.
      if (!$feed->isActive() && !$options['import-disabled']) {
        throw new \Exception($this->t('The specified feed is disabled. If you want to force importing, specify --import-disabled.'));
      }

      if (!$this->io()->confirm($this->t('Do you really want to import the feed ":label" (id :id)?', [':label' => $feed->label(), ':id' => $fid]))) {
        throw new UserAbortException();
      }

      // User has confirmed importing, start import!
      $feed->import();
    }
    else {
      throw new \Exception($this->t('There is no feed with id :id', [':id' => $fid]));
    }
  }

  /**
   * Lock a feed specified by its id.
   *
   * @param int $fid
   *   The id of the feed which should get locked.
   *
   * @command feeds:lock
   * @aliases feeds-lk
   * @usage feeds:lock 1
   */
  public function lockFeed($fid = NULL) {
    if (empty($fid)) {
      throw new \Exception($this->t('Please specify the ID of the feed you want to lock.'));
    }
    $feed = $this->getFeed($fid);

    // Check if the feed we got is valid.
    if ($feed instanceof FeedInterface) {
      if ($feed->isLocked()) {
        $this->logger()->notice($this->t('This feed is already locked.'));
        return;
      }
      if (!$this->io()->confirm($this->t('The following feed will be locked: ":label" (id :id)', [':label' => $feed->label(), ':id' => $fid]))) {
        throw new UserAbortException();
      }

      $feed->lock();
      $this->logger->success($this->t('The feed ":label" has been locked.', [':label' => $feed->label()]));
    }
    else {
      throw new \Exception($this->t('There is no feed with id :id', [':id' => $fid]));
    }
  }

  /**
   * Unlock a feed specified by its id.
   *
   * @param int $fid
   *   The id of the feed which should get unlocked.
   *
   * @command feeds:unlock
   * @aliases feeds-ulk
   * @usage feeds:unlock 1
   */
  public function unlockFeed($fid = NULL) {
    if (empty($fid)) {
      throw new \Exception($this->t('Please specify the ID of the feed you want to unlock.'));
    }
    $feed = $this->getFeed($fid);

    // Check if the feed we got is valid.
    if ($feed instanceof FeedInterface) {
      if (!$feed->isLocked()) {
        $this->logger()->notice($this->t('This feed is already unlocked.'));
        return;
      }
      if (!$this->io()->confirm($this->t('The following feed will be unlocked: ":label" (id :id)', [':label' => $feed->label(), ':id' => $fid]))) {
        throw new UserAbortException();
      }

      $feed->unlock();
      $this->logger->success($this->t('The feed ":label" has been unlocked.', [':label' => $feed->label()]));
    }
    else {
      throw new \Exception($this->t('There is no feed with id :id', [':id' => $fid]));
    }
  }

  /**
   * Get the feed entity by ID.
   *
   * @param int $fid
   *   The ID of the feed.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The Feed entity when loaded successfully, null otherwise.
   */
  private function getFeed($fid) {
    try {
      // Load the feed entity.
      return \Drupal::entityTypeManager()
        ->getStorage('feeds_feed')
        ->load($fid);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error($e->getMessage());
    }
    // An error seems to have occurred when getting here, return null.
    return NULL;
  }

  /**
   * Translates a string using the dt function.
   *
   * @param string $message
   *   The message to translate.
   * @param array $arguments
   *   (optional) The translation arguments.
   *
   * @return string
   *   The translated message.
   */
  protected function t($message, array $arguments = []) {
    return dt($message, $arguments);
  }

}
