<?php

/**
 * Aggregates the stats from all checks into hourly buckets
 */
class StatisticsAggregator
{
  /**
   * @var array
   */
  protected $hourly_stats = array();

  /**
   * @param int   $time_window
   * @param float $uptime
   * @param int   $response_time
   * @param int   $count
   */
  public function add_statistic($time_window, $uptime, $response_time, $count = 1)
  {
    if ( ! isset($this->hourly_stats[$time_window])) {
      $this->hourly_stats[$time_window] = array('uptime' => 0, 'response' => 0, 'count' => 0);
    }
    $this->hourly_stats[$time_window]['uptime']   += $uptime;
    $this->hourly_stats[$time_window]['response'] += $response_time;
    $this->hourly_stats[$time_window]['count']    += $count;
  }

  /**
   * @return array
   */
  public function get_statistics()
  {
    $metrics = array();
    $totals = array('uptime' => 0, 'response' => 0, 'count' => 0);
    foreach ($this->hourly_stats as $time_window => $stats) {
      $totals['uptime'] += $stats['uptime'];
      $totals['response'] += $stats['response'];
      $totals['count'] += $stats['count'];

      $metrics[$time_window]['uptime']   = $stats['uptime'] / $stats['count'];
      $metrics[$time_window]['response'] = $stats['response'] / $stats['count'];
      $metrics[$time_window]['totals']   = $stats;
    }

    return array(
        'uptime'   => $totals['uptime'] / $totals['count'],
        'response' => $totals['response'] / $totals['count'],
        'hourly'   => $metrics
    );
  }
}
