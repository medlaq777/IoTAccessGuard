<?php

namespace App\Services;


use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttService
{
    private $client;

    public function __construct($broker, $port, $clientId)
    {
        $this->client = new MqttClient($broker, $port, $clientId);
    }

    public function connect($username = null, $password = null)
    {
        $settings = (new ConnectionSettings())
            // ->setUsername($username)
            // ->setPassword($password)
            ->setKeepAliveInterval(60);

        $this->client->connect($settings);
    }

    public function publish($topic, $message)
    {
        $this->client->publish($topic, $message, 0);
    }

    public function subscribe($topic, callable $callback)
    {
        $this->client->subscribe($topic, $callback);
        $this->client->registerLoopEventHandler(function ($topic, $message) use ($callback) {
            $callback($topic, $message);
        });
        $this->client->loop(true);
    }

    public function disconnect()
    {
        $this->client->disconnect();
    }

}