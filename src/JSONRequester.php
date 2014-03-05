<?php
use Guzzle\Http\Exception\BadResponseException;

/**
 * Requests JSON by HTTP
 */
class JSONRequester
{
  /**
   * @var Guzzle\Http\Client
   */
  protected $guzzle;

  /**
   * @param \Guzzle\Http\Client $guzzle
   */
  public function __construct(Guzzle\Http\Client $guzzle)
  {
    $this->guzzle = $guzzle;
  }

  /**
   * @param string   $url
   * @param string[] $query
   *
   * @return array
   * @throws Guzzle\Http\Exception\BadResponseException
   */
  public function get_json($url, $query)
  {
    $request = $this->guzzle->get($url, array(), array('query' => $query));
    $response = $request->send();

    return $this->parse_response_json($response);
  }

  /**
   * @param \Guzzle\Http\Message\Response $response
   *
   * @throws BadResponseException
   * @return mixed
   */
  protected function parse_response_json($response)
  {
    if ( ! $data = json_decode($response->getBody(TRUE), TRUE)) {
      throw new BadResponseException('Could not parse JSON from result'.\PHP_EOL.$response->getBody(TRUE));
    }

    return $data;
  }
}
