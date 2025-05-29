<?php

namespace App\Console\Commands;

use Exception;
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
                "commandid" => 1,
                "operation" => "PUT",
                "resource" => "device/doors/1/lock/status?value=closeKeepOff",
                "data" => [
                    "floor" => "0000000000000001",
                    "eleKeyMode" => 0,
                ]
            ]);
            // $payload = json_encode([
            //     "resource" => "openDoor/result",
            //     "serial" => 205074, // the returned serial needs to be consistent with the serial sent when the device requests it
            //     "data" => [
            //         "ret" => 0, // 0: success
            //         "msg" => "ok" // result description
            //     ]
            // ]);
            $mqtt->publish('test', $payload);
            $this->info("Published message to topic 'test': " . $payload);
            $mqtt->loop();
        } catch (ProtocolNotSupportedException $e) {
            $this->error("Protocol not supported: " . $e->getMessage());
        } catch (Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
