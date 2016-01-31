<?php

namespace Calibr\EhApiClient;

class Exception extends \Exception {
  private $_name;
  private $_httpCode;

  public function getName() {
    return $this->_name;
  }

  public function getHttpCode() {
    return $this->_httpCode;
  }

  public function setHttpCode($code) {
    $this->_httpCode = $code;
  }

  public function __construct($name = "Error", $message = "") {
    if(!is_string($name)) {
      $name = var_export($name, true);
    }
    if(!is_string($message)) {
      $message = var_export($message, true);
    }
    parent::__construct($name." (".$message.")");
    $this->_name = $name;
  }
}