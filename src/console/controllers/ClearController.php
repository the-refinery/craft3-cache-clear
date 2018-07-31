<?php
/**
 * CacheClear plugin for Craft CMS 3.x
 *
 * This is a generic Craft CMS plugin
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2018 The Refinery
 */

namespace therefinery\cacheclear\console\controllers;

use therefinery\cacheclear\CacheClear;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;
use craft\utilities\ClearCaches;

/**
 * Command to selectively clear CraftCMS Caches
 *
 * Please run the following command to see a list of caches that are available:
 * ./craft cache-clear/clear/list
 *
 * The first line of this class docblock is displayed as the description
 * of the Console Command in ./craft help
 *
 * Craft can be invoked via commandline console by using the `./craft` command
 * from the project root.
 *
 * Console Commands are just controllers that are invoked to handle console
 * actions. The segment routing is plugin-name/controller-name/action-name
 *
 * The actionIndex() method is what is executed if no sub-commands are supplied, e.g.:
 *
 * ./craft cache-clear/clear
 *
 * Actions must be in 'kebab-case' so actionDoSomething() maps to 'do-something',
 * and would be invoked via:
 *
 * ./craft cache-clear/clear/do-something
 *
 * @author    The Refinery
 * @package   CacheClear
 * @since     1.0.0
 */
class ClearController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Runs the cache-clear plugin against selected CraftCMS caches.
     *
     * @return mixed
     */
    public function actionIndex()
    {
      $pathService = Craft::$app->getPath();

      $result = 'something';

      if(sizeof(Craft::$app->getRequest()->getParams()) == 1) {
        Craft::info("CacheClear: No caches were given.");
        echo "No cachces were given. Nothing to do.\n";
        echo "Please select from the following caches:\n";
        foreach($this->cacheListForOutput() as $item) {
          echo " * $item\n";
        }
        return;
      }

      $caches = [];

      foreach(array_slice(Craft::$app->getRequest()->getParams(), 1) as $cache) {
        Craft::trace("CacheClear: adding $cache to be processed");
        array_push($caches, $cache);
      }

      $cmsOptions = $this->cmsCacheOptions();

      foreach($caches as $cacheName) {
        if(!isset($cmsOptions[$cacheName])) {
          Craft::error("CacheClear: cache name '$cacheName' is not an available cache to clear.");
          throw new \Exception("Cache label '$cacheName' is not valid.");
        }
      }

      // If we've made it this far, then every cache that was passed to us is valid.  Let's run them.
      foreach($caches as $cacheName) {
        Craft::info("CacheClear: Running clear cache for $cacheName");
        echo "Running cache clearing action for $cacheName\n";
        $this->perform($cmsOptions[$cacheName]["action"]);
      }

      return $result;
    }

    /**
     * Returns a list of available caches.
     *
     * @return mixed
     */
    public function actionList() {
      echo "The caches available to clear are:\n";
      foreach($this->cacheListForOutput() as $item) {
        echo " * $item\n";
      }
    }

    private function cmsCacheOptions() {
      $values = [];

      foreach(ClearCaches::cacheOptions() as $option) {
        $values[$option["key"]] = array(
          "label" => $option["label"],
          "action" => $option["action"]
        );
      }

      return $values;
    }

    private function cacheListForOutput() {
      $values = [];
      foreach($this->cmsCacheOptions() as $key => $value) {
        array_push($values, $key." (".$value["label"].")");        
      }
      return $values;
    }

    private function perform($action) {
      if (is_string($action)) {
	try {
	  FileHelper::clearDirectory($action);
	} catch (InvalidArgumentException $e) {
	  // the directory doesn't exist
	  Craft::warning("Could not clear the directory {$action}: " . $e->getMessage(), __METHOD__);
	} catch (\Throwable $e) {
	  Craft::warning("Could not clear the directory {$action}: " . $e->getMessage(), __METHOD__);
	}
      }
      else {
	$action();
      }
    }
}
