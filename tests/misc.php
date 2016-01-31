<?php

function getClient($mock) {
  $client = new \Calibr\EhApiClient\Client("http://base.com");
  $client->setHTTPClient($mock);
  return $client;
}