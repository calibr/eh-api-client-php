<?php

require "./vendor/autoload.php";

class ClientExceptionTest extends PHPUnit_Framework_TestCase
{
  public function testArrayData() {
    $ex = new \Calibr\EhApiClient\Exception(["test"], ["msg"]);
    $this->assertEquals($ex->getName(), var_export(["test"], true));
    $this->assertEquals($ex->getMessage(), var_export(["test"], true)." (".var_export(["msg"], true).")");
  }
}