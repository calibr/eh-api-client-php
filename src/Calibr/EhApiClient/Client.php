<?php

namespace Calibr\EhApiClient;

class Client {
  private $_client;
  private $_baseUrl;
  private $_internalAuth;

  public function __construct($baseUrl) {
    $this->_client = new \GuzzleHttp\Client();
    $this->_baseUrl = $baseUrl;
  }

  public function setHTTPClient($client) {
    $this->_client = $client;
  }

  public function setInternalAuth($userId, $app) {
    $this->_internalAuth = "$userId:$app";
  }

  private function _prepareUrl($url) {
    $result = $url;
    if(is_array($url)) {
      $result = array_shift($url);
      $result = preg_replace_callback("@\?\?@", function($m) use (&$url) {
        return array_shift($url);
      }, $result);
    }
    return $result;
  }

  private function _checkResponseError($res) {
    if($res->getStatusCode() < 200 || $res->getStatusCode() > 299) {
      $body = json_decode($res->getBody()->getContents(), true);
      if($body) {
        $name = "Error";
        $message = "";
        if(isset($body["name"])) {
          $name = $body["name"];
        }
        if(isset($body["message"])) {
          $message = $body["message"];
        }
        $ex = new Exception($name, $message);
      }
      else {
        $ex = new Exception("Error ".$res->getStatusCode());
      }
      $ex->setHttpCode($res->getStatusCode());
      throw $ex;
    }
  }

  private function _parseResponse($res) {
    $body = json_decode($res->getBody()->getContents(), true);
    return $body;
  }

  private function _arrayFirstKey($filter) {
    $keys = array_keys($filter);
    if(!$keys) {
      return null;
    }
    return $keys[0];
  }

  private function _arrayIsAssoc($arr) {
    $keys = array_keys($arr);
    $range = range(0, count($arr) - 1);
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  private function _parseFilters($filters) {
    $qs = [];
    $filterFields = [];
    foreach($filters as $filter) {
      if(!is_array($filter)) {
        continue;
      }
      if(!isset($filter["field"])) {
        $key = $this->_arrayFirstKey($filter);
        $filter["field"] = $key;
        $value = $filter[$key];
        $type = "eq";
        if(is_array($value)) {
          if($this->_arrayIsAssoc($value)) {
            $type = $this->_arrayFirstKey($value);
            $value = $value[$type];
          }
          else {
            $type = "in";
          }
        }
        $filter["value"] = $value;
        $filter["type"] = $type;
      }
      $filterFields[] = $filter["field"];
      $qs["filterValue_".$filter["field"]] = $filter["value"];
      $qs["filterType_".$filter["field"]] = $filter["type"];
    }
    $qs["filterFields"] = $filterFields;
    return $qs;
  }

  private function _reqOptions($options = []) {
    $options = [
      "headers" => isset($options["headers"]) ? $options["headers"] : [],
      "query" => isset($options["query"]) ? $options["query"] : []
    ];
    if($this->_internalAuth) {
      $options["headers"]["Authorization"] = "Internal ".$this->_internalAuth;
    }
    return $options;
  }

  /**
   * @param  string $method  http method
   * @param  string $url
   * @param  mixed [$body]   request body
   * @param  array  [$options] request options
   */
  private function _request($method, $url, $body = null, $options = []) {
    if(is_array($body)) {
      $options["json"] = $body;
    }
    else {
      $options["body"] = $body;
    }
    $url = $this->_prepareUrl($url);
    $url = $this->_baseUrl . $url;
    $res = $this->_callClient($method, $url, $options);
    $this->_checkResponseError($res);
    return $this->_parseResponse($res);
  }

  private function _callClient($method, $url, $options) {
    $options["http_errors"] = false;
    return call_user_func([$this->_client, $method], $url, $options);
  }

  /**
   * @param  string $url
   * @param  array  [$options]
   * @param  array  [$options.filter]
   * @param  string  [$options.range]
   */
  public function get($url, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    if(isset($options["filter"])) {
      $reqOptions["query"] = array_merge($reqOptions["query"], $this->_parseFilters($options["filter"]));
    }
    if(isset($options["range"])) {
      $reqOptions["headers"]["Range"] = $options["range"];
    }
    return $this->_request("get", $url, null, $reqOptions);
  }

  /**
   * @param  string $url
   * @param  array  [$options]
   */
  public function head($url, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    $this->_request("head", $url, null, $reqOptions);
    return null;
  }

  /**
   * check resource is exists by HEAD request
   * returns true if response code is 2XX returns true
   * if response code is 404 returns false
   * otherwise throws an exception
   * @return bool
   */
  public function exists($url, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    $res = true;
    try {
      $this->_request("head", $url, null, $reqOptions);
    }
    catch(Exception $ex) {
      if($ex->getHttpCode() === 404) {
        $res = false;
      }
      else {
        throw $ex;
      }
    }
    return $res;
  }

  /**
   * @param  string $url
   * @param  mixed  $body
   * @param  array  [$options]
   */
  public function post($url, $body, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    return $this->_request("post", $url, $body, $reqOptions);
  }

  /**
   * @param  string $url
   * @param  mixed  $body
   * @param  array  [$options]
   */
  public function put($url, $body, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    return $this->_request("put", $url, $body, $reqOptions);
  }

  /**
   * @param  string $url
   * @param  mixed  $body
   * @param  array  [$options]
   */
  public function patch($url, $body, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    return $this->_request("patch", $url, $body, $reqOptions);
  }

  /**
   * @param  string $url
   * @param  array  [$options]
   */
  public function delete($url, $options = []) {
    $reqOptions = $this->_reqOptions($options);
    return $this->_request("delete", $url, null, $reqOptions);
  }
}