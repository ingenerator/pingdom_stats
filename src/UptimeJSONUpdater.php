<?php
use Aws\S3\Exception\NoSuchKeyException;
use Aws\S3\S3Client;

/**
 * Updates the uptime JSON and stores to S3
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2014 inGenerator Ltd
 * @licence   BSD
 */
class UptimeJSONUpdater {

  /**
   * @var Aws\S3\S3Client
   */
  protected $s3;

  /**
   * @var UptimeJSONBuilder
   */
  protected $builder;

  /**
   * @var string
   */
  protected $bucket;

  /**
   * @var string
   */
  protected $key;

  /**
   * @var int[]
   */
  protected $check_ids;

  /**
   * @param S3Client          $s3
   * @param UptimeJSONBuilder $builder
   * @param string            $bucket
   * @param string            $key
   * @param int[]             $check_ids
   */
  public function __construct(S3Client $s3, UptimeJSONBuilder $builder, $bucket, $key, $check_ids)
  {
    $this->s3 = $s3;
    $this->builder = $builder;
    $this->bucket = $bucket;
    $this->key = $key;
    $this->check_ids = $check_ids;
  }

  public function execute()
  {
    $previous = $this->try_fetch_previous_json();
    $json     = $this->builder->build_json($this->check_ids, $previous);
    $this->upload_json($json);
  }

  protected function try_fetch_previous_json()
  {
    try
    {
      $object = $this->s3->getObject(array('Bucket' => $this->bucket, 'Key' => $this->key));
      $json = json_decode( (string) $object['Body'], TRUE);
      if ( ! $json) {
        throw new \Exception("Invalid JSON returned from ".$this->bucket.'/'.$this->key);
      }
      return $json;
    }
    catch (NoSuchKeyException $e)
    {
      print "!! No previous stats to load".\PHP_EOL;
      return array('hourly' => array());
    }
  }

  protected function upload_json($json)
  {
    print "Uploading to S3::".$this->bucket.'/'.$this->key.\PHP_EOL;
    $this->s3->putObject(array(
            'ACL'         => 'public-read',
            'Body'        => $json,
            'Bucket'      => $_SERVER['UPTIME_JSON_BUCKET'],
            'ContentType' => 'application/json',
            'Expires'     => new \DateTime('+1 hour'),
            'Key'         => UPTIME_JSON_FILE,
    ));
    print "Done".\PHP_EOL;
  }

}
