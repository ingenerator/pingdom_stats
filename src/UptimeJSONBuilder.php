<?php
/**
 * Grabs the uptime data from Pingdom and builds it to JSON
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2014 inGenerator Ltd
 * @licence   BSD
 */
class UptimeJSONBuilder {

  const MAX_CHUNK_LENGTH   = 432000; // 5 days as seconds
  const MAX_DAYS_TO_REPORT = 120;

  /**
   * @var JSONRequester
   */
  protected $pingdom;

  /**
   * @var StatisticsAggregator
   */
  protected $statistics;

  /**
   * @var int[]
   */
  protected $check_ids;

  /**
   * @var int
   */
  protected $earliest_time;

  /**
   * @param JSONRequester        $pingdom
   * @param StatisticsAggregator $statistics
   */
  public function __construct(JSONRequester $pingdom, StatisticsAggregator $statistics)
  {
    $this->pingdom = $pingdom;
    $this->statistics = $statistics;
    $this->earliest_time = time() - (86400 * self::MAX_DAYS_TO_REPORT);
  }

  /**
   * @param int[] $check_ids
   * @param array $previous_json
   *
   * @return string
   */
  public function build_json($check_ids, $previous_json)
  {
    $this->check_ids = $check_ids;
    $import_from_time = $this->add_previous_data_and_find_last_time($previous_json);
    $this->display_active_checks();
    $this->import_for_time_range($import_from_time, time());
    return json_encode($this->statistics->get_statistics());
  }

  /**
   * @param array $previous_json
   *
   * @return int
   */
  protected function add_previous_data_and_find_last_time($previous_json)
  {
    if ( ! $previous_json['hourly']) {
      print "No previous metrics to build from".\PHP_EOL;
    }
    $time_window = $this->earliest_time;

    print "Starting from previously imported statistics".\PHP_EOL;
    foreach ($previous_json['hourly'] as $time_window => $stats)
    {
      if ($time_window > $this->earliest_time) {
        $this->statistics->add_statistic($time_window, $stats['totals']['uptime'], $stats['totals']['response'], $stats['totals']['count']);
      } else {
        print "Dropping old metrics for ".date('c', $time_window).\PHP_EOL;
      }
    }

    return $time_window;
  }

  /**
   *
   */
  protected function display_active_checks()
  {
    print "Requesting list of all checks for information".\PHP_EOL;
    $checks = $this->pingdom->get_json('checks', array());
    foreach ($checks['checks'] as $check)
    {
      $in = in_array($check['id'], $this->check_ids) ? '[IN]' : '[  ]';
      print $in."\t| ".$check['id']."\t| ".$check['name'].\PHP_EOL;
    }
  }

  /**
   * @param int $start_time
   * @param int $end_time
   */
  protected function import_for_time_range($start_time, $end_time)
  {
    for ($chunk_start = $start_time; $chunk_start < $end_time; $chunk_start += self::MAX_CHUNK_LENGTH)
    {
      $this->import_time_chunk($chunk_start, $chunk_start + self::MAX_CHUNK_LENGTH);
    }
  }

  /**
   * @param int $start_time
   * @param int $end_time
   */
  protected function import_time_chunk($start_time, $end_time)
  {
    print "Importing stats for period ".date('c', $start_time)." - ".date('c', $end_time).\PHP_EOL;
    foreach ($this->check_ids as $check_id)
    {
      print " - Check $check_id ";
      $this->import_statistics($this->pingdom->get_json(
        "summary.performance/$check_id",
        array('from' => $start_time, 'to' => $end_time, 'includeuptime' => 'true', 'resolution' => 'hour')
      ));
      print " - OK".\PHP_EOL;
    }
  }

  /**
   * @param array $summary_stats
   */
  protected function import_statistics($summary_stats)
  {
    foreach ($summary_stats['summary']['hours'] as $hourly_data)
    {
      $this->statistics->add_statistic($hourly_data['starttime'], (3600 - $hourly_data['downtime']) / 3600, $hourly_data['avgresponse']);
    }
  }

  /**
   * @param string $output_path
   */
  protected function export_json($output_path)
  {
    print "Calculating averages ".PHP_EOL;
    file_put_contents($output_path, json_encode($this->statistics->get_statistics()));
  }

} 
