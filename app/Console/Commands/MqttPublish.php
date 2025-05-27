<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT as LaravelMqtt;
use PhpMqtt\Client\Exceptions\ProtocolNotSupportedException;

class MqttPublish extends Command
{
    protected $signature = "mqtt:publish {topic} {message}";
    protected $description = "Publish a message to an MQTT topic";

    public function handle()
    {
        try {
            $mqtt = LaravelMqtt::connection();
            $mqtt->connect();
            $message = [
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'topic' => $this->argument('topic'),
                    'message' => $this->argument('message')
                ]
            ];
            $mqtt->publish('test', json_encode($message), 0);
            $this->info("Published message to topic 'test': " . json_encode($message));
            $mqtt->loop();
        } catch (ProtocolNotSupportedException $e) {
            $this->error("Protocol not supported: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}