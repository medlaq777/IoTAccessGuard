<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT as LaravelMqtt;
use PhpMqtt\Client\Exceptions\ProtocolNotSupportedException;

class MqttPublish extends Command
{
    protected $signature = "mqtt:publish {topic}";
    protected $description = "Publish a message to an MQTT topic";

    public function handle()
    {
        try {
            $mqtt = LaravelMqtt::connection();
            $mqtt->connect();
            $payload = json_encode([
                "resource" => "password",
                "serial" => 205074,
                "data" => [
                    "ret" => 0,
                    "msg"=> "ok",
                ]
            ]);
            $mqtt->publish('test', $payload, 0);
            $this->info("Published message to topic 'test': " . $payload);
            $mqtt->loop();
        } catch (ProtocolNotSupportedException $e) {
            $this->error("Protocol not supported: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
