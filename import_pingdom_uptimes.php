<?php
/**
 * Grabs the uptime and response time summaries for a set of Pingdom checks and aggregates them to JSON
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2014 inGenerator Ltd
 * @licence   BSD
 */

require_once(__DIR__.'/env.php');
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/src/JSONRequester.php');
require_once(__DIR__.'/src/StatisticsAggregator.php');
require_once(__DIR__.'/src/UptimeJSONBuilder.php');
require_once(__DIR__.'/src/UptimeJSONUpdater.php');

use Aws\S3\S3Client;

// Verify environment variables are set
foreach (array('PINGDOM_KEY', 'PINGDOM_USER', 'PINGDOM_PW', 'PINGDOM_INCLUDE_CHECKS', 'AWS_ACCESS_KEY_ID', 'AWS_SECRET_KEY', 'UPTIME_JSON_BUCKET') as $required_key) {
  if ( ! isset($_SERVER[$required_key])) {
    throw new \InvalidArgumentException("The environment variable $required_key is not set");
  }
}
define('UPTIME_JSON_FILE', 'client_uptime.json');

// Create and execute task
$updater = new UptimeJSONUpdater(
  S3Client::factory(array('region' => 'eu-west-1')),
  new UptimeJSONBuilder(
    new JSONRequester(new Guzzle\Http\Client('https://api.pingdom.com/api/2.0', array(
        'request.options' => array(
            'headers' => array('App-Key' => $_SERVER['PINGDOM_KEY']),
            'auth'    => array($_SERVER['PINGDOM_USER'], $_SERVER['PINGDOM_PW'], 'Basic')
        )
    ))),
    new StatisticsAggregator
  ),
  $_SERVER['UPTIME_JSON_BUCKET'],
  UPTIME_JSON_FILE,
  explode(',', $_SERVER['PINGDOM_INCLUDE_CHECKS'])
);

$updater->execute();
