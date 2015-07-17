<?php

require "./vendor/autoload.php";

use GuzzleHttp\Psr7\Response;
//require __DIR__."/../src/Calibr/EhApiClient/Client.php";

//use \GuzzleHttp\Client;

function getClient($mock) {
  $client = new \Calibr\EhApiClient\Client("http://base.com");
  $client->setHTTPClient($mock);
  return $client;
}

class ClientGetTest extends PHPUnit_Framework_TestCase
{
  private function _getMock() {
    $mock = $this->getMockBuilder("\GuzzleHttp\Client")
                 ->setMethods(array("get"))
                 ->getMock();
    return $mock;
  }

  private function _getMockSuccess() {
    $res = new Response(200, [], json_encode([]));
    $mock = $this->_getMock();
    $mock->method("get")
         ->willReturn($res);
    return $mock;
  }

  private function _getMockError($name, $code, $message = "") {
    $res = new Response($code, [], json_encode([
      "name" => $name,
      "message" => $message
    ]));
    $mock = $this->_getMock();
    $mock->method("get")
         ->willReturn($res);
    return $mock;
  }

  public function testSimpleGet() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("get")
         ->with("http://base.com/testdata", [
           "headers" => [],
           "query" => [],
           "body" => null,
           "http_errors" => false
         ]);
    $client->get("/testdata");
  }

  public function testSimpleGetWithCompositeUrl() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("get")
         ->with("http://base.com/testdata/1/2/3", [
           "headers" => [],
           "query" => [],
           "body" => null,
           "http_errors" => false
         ]);
    $client->get(["/testdata/??/??/??", 1, 2, 3]);
  }

  public function testGetWithFilter() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("get")
         ->with("http://base.com/testdata", [
           "headers" => [],
           "query" => [
             "filterFields" => ["directField", "field2", "field3", "field4"],
             "filterValue_directField" => 10,
             "filterType_directField" => "gt",
             "filterValue_field2" => "just.string",
             "filterType_field2" => "eq",
             "filterValue_field3" => ["val1", "val2", "val3"],
             "filterType_field3" => "in",
             "filterValue_field4" => 500,
             "filterType_field4" => "lte"
           ],
           "body" => null,
           "http_errors" => false
         ]);
    $client->get("/testdata", [
      "filter" => [
        ["field" => "directField", "type" => "gt", "value" => 10],
        ["field2" => "just.string"],
        ["field3" => ["val1", "val2", "val3"]],
        ["field4" => ["lte" => 500]]
      ]
    ]);
  }

  public function testGetWithRange() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("get")
         ->with("http://base.com/testdata", [
           "headers" => [
             "Range" => "items=0-10"
           ],
           "query" => [],
           "body" => null,
           "http_errors" => false
         ]);
    $client->get("/testdata", [
      "range" => "items=0-10"
    ]);
  }

  public function testGetWithCustomQueryString() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("get")
         ->with("http://base.com/testdata", [
           "query" => [
             "key1" => "val1",
             "key2" => "val2"
           ],
           "body" => null,
           "headers" => [],
           "http_errors" => false
         ]);
    $client->get("/testdata", [
      "query" => [
        "key1" => "val1",
        "key2" => "val2"
      ]
    ]);
  }

  public function testGetWithInternalAuth() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $client->setInternalAuth(1, "web");
    $mock->expects($this->once())
         ->method("get")
         ->with("http://base.com/testdata", [
           "query" => [],
           "body" => null,
           "headers" => [
             "Authorization" => "Internal 1:web"
           ],
           "http_errors" => false
         ]);
    $client->get("/testdata");
  }

  public function testGetWithErrorResponse() {
    $mock = $this->_getMockError("NotFound", 404, "data not found");
    $client = getClient($mock);
    try {
      $client->get("/testdata");
      $this->fail("expected exception not thrown");
    }
    catch(\Calibr\EhApiClient\Exception $ex) {
      $this->assertEquals($ex->getName(), "NotFound");
      $this->assertEquals($ex->getHttpCode(), 404);
    }
  }

  public function testGetWithDataResponse() {
    $mock = $this->_getMock();
    $res = new Response(200, [], json_encode([
      "key1" => "val1",
      "key2" => ["val2", "val3"]
    ]));
    $mock->method("get")
         ->willReturn($res);
    $client = getClient($mock);
    $data = $client->get("/testdata");
    $this->assertEquals($data, [
      "key1" => "val1",
      "key2" => ["val2", "val3"]
    ]);
  }
}

class ClientHeadTest extends PHPUnit_Framework_TestCase
{
  private function _getMock() {
    $mock = $this->getMockBuilder("\GuzzleHttp\Client")
                 ->setMethods(array("head"))
                 ->getMock();
    return $mock;
  }

  private function _getMockSuccess() {
    $res = new Response(200, [], json_encode([]));
    $mock = $this->_getMock();
    $mock->method("head")
         ->willReturn($res);
    return $mock;
  }

  private function _getMockError($code) {
    $res = new Response($code, [], null);
    $mock = $this->_getMock();
    $mock->method("head")
         ->willReturn($res);
    return $mock;
  }

  public function testSimpleHead() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $mock->expects($this->once())
         ->method("head")
         ->with("http://base.com/testdata", [
           "headers" => [],
           "query" => [],
           "body" => null,
           "http_errors" => false
         ]);
    $data = $client->head("/testdata");
    $this->assertEquals($data, null);
  }

  public function testHeadWithError() {
    $mock = $this->_getMockError(404);
    $client = getClient($mock);
    try {
      $client->head("/testdata");
      $this->fail("expected exception not thrown");
    }
    catch(\Calibr\EhApiClient\Exception $ex) {
      $this->assertEquals($ex->getHttpCode(), 404);
    }
  }

}

class ClientExistsTest extends PHPUnit_Framework_TestCase
{
  private function _getMock() {
    $mock = $this->getMockBuilder("\GuzzleHttp\Client")
                 ->setMethods(array("head"))
                 ->getMock();
    return $mock;
  }

  private function _getMockSuccess() {
    $res = new Response(200, [], json_encode([]));
    $mock = $this->_getMock();
    $mock->method("head")
         ->willReturn($res);
    return $mock;
  }

  private function _getMockError($code) {
    $res = new Response($code, [], null);
    $mock = $this->_getMock();
    $mock->method("head")
         ->willReturn($res);
    return $mock;
  }

  public function testExistsFound() {
    $mock = $this->_getMockSuccess();
    $client = getClient($mock);
    $data = $client->exists("/testdata");
    $this->assertEquals($data, true);
  }

  public function testExistsNotFound() {
    $mock = $this->_getMockError(404);
    $client = getClient($mock);
    $data = $client->exists("/testdata");
    $this->assertEquals($data, false);
  }

  public function testExistsServerError() {
    $mock = $this->_getMockError(500);
    $client = getClient($mock);
    try {
      $client->head("/testdata");
      $this->fail("expected exception not thrown");
    }
    catch(\Calibr\EhApiClient\Exception $ex) {
      $this->assertEquals($ex->getHttpCode(), 500);
    }
  }
}



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