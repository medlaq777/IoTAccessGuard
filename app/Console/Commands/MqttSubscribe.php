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

            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->info("Received message on topic {$topic}: {$message}");
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
