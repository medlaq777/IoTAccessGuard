<?php

namespace App\Console\Commands;

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
                $this->info("Received message on topic {$topic}: {$message}");
                $data = json_decode($message, true);

                if ($data) {
                    $this->publish([
                        'code' => 0,
                        'msg' => 'success',
                        'data' => [
                            'SN' => $data['SN'],
                            'operation' => $data['operation'],
                            'resource' => $data['resource'],
                            'value' => $data['data']['value'],
                        ]
                    ], 'test', 0);
                    // Perform additional handling logic here
                } else {
                    $this->error("Invalid message structure.");
                }
            });
            while (true) {
                $mqtt->loop();
            }
        } catch (ProtocolNotSupportedException $e) {
            $this->error("Protocol not supported: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
