<?php

require "./vendor/autoload.php";

use GuzzleHttp\Psr7\Response;

require_once "./tests/misc.php";

class ClientPostTest extends PHPUnit_Framework_TestCase
{
  private function _getMock() {
    $mock = $this->getMockBuilder("\GuzzleHttp\Client")
                 ->setMethods(array("post"))
                 ->getMock();
    return $mock;
  }

  private function _getMockSuccess() {
    $res = new Response(200, [], json_encode(["result" => true]));
    $mock = $this->_getMock();
    $mock->method("post")
         ->willReturn($res);
    return $mock;
  }

  private function _getMockError($name, $code, $message = "") {
    $res = new Response($code, [], json_encode([
      "name" => $name,
      "message" => $message
    ]));
    $mock = $this->_getMock();
    $mock->method("post")
         ->willReturn($res);
    return $mock;
  }

  public function testPostArray() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("post")
         ->with("http://base.com/testdata", [
           "headers" => [],
           "query" => [],
           "json" => [
             "text" => "Hello world"
           ],
           "http_errors" => false
         ]);
    $res = $client->post("/testdata", [
      "text" => "Hello world"
    ]);
    $this->assertEquals($res["result"], true);
  }

  public function testPostString() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("post")
         ->with("http://base.com/testdata", [
           "headers" => [],
           "query" => [],
           "body" => "Hello world",
           "http_errors" => false
         ]);
    $res = $client->post("/testdata", "Hello world");
    $this->assertEquals($res["result"], true);
  }

  public function testPostWithQueryString() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("post")
         ->with("http://base.com/testdata", [
           "headers" => [],
           "query" => ["hello" => "world"],
           "body" => "Hello world",
           "http_errors" => false
         ]);
    $res = $client->post("/testdata", "Hello world", [
      "query" => ["hello" => "world"]
    ]);
    $this->assertEquals($res["result"], true);
  }
}