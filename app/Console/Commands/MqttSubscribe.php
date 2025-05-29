<?php

namespace App\Console\Commands;

use Exception as GlobalException;
use Illuminate\Console\Command;
use PhpMqtt\Client\Exceptions\ProtocolNotSupportedException;
use PhpMqtt\Client\Facades\MQTT as LaravelMqtt;

class MqttSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "mqtt:subscribe {topic}";

    protected $description = "Subscribe to an MQTT topic and listen for messages";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $topic = $this->argument('topic');

        try {
            $mqtt = LaravelMqtt::connection();

            $mqtt->connect();

            $mqtt->subscribe($topic, function ($topic, $message) use ($mqtt) {
                $this->info("Sub => {$topic} => {$message}");
                $decodedMessage = json_decode($message, true);
                if (isset($decodedMessage["resource"]) && $decodedMessage["resource"] === 'qrcode') {
                    $payload = json_encode([
                        "resource" => "qrcode",
                        "serial" => 205074,
                        "data" => [
                            "ret" => 0,
                            "message" => "ok",
                        ]
                    ]);
                    $mqtt->publish('test', $payload, 2);
                    $this->info("Published message to topic 'test': " . $payload);
                    $mqtt->unsubscribe($topic);
                }else {
                    $payload = json_encode([
                        "resource" => "cardno",
                        "data" => [
                            "ret" => 0,
                            "message" => "ok",
                        ]
                    ]);
                    $mqtt->publish('test', $payload, 2);
                    $this->info("Published message to topic 'test': " . $payload);
                    $mqtt->unsubscribe($topic);
                }
            }, 0);

            while (true) {
                $mqtt->loop();
            }
        } catch (ProtocolNotSupportedException $e) {
            $this->error("Protocol not supported: " . $e->getMessage());
        } catch (GlobalException $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
