<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\Exceptions\ProtocolNotSupportedException;
use PhpMqtt\Client\Exceptions\RepositoryException;
use PhpMqtt\Client\Facades\MQTT as LaravelMqtt;

class MqttSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "mqtt:subscribe 
        {topic : The MQTT topic to subscribe to}
        {--qos=1 : Quality of Service level (0, 1, or 2)}
        {--retain=0 : Whether the server should retain the message (0 or 1)}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Subscribe to an MQTT topic and listen for messages";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $topic = $this->argument('topic');
        $qos = (int) $this->option('qos');
        $retain = (bool) $this->option('retain');

        // Validate QoS level
        if ($qos < 0 || $qos > 2) {
            $this->error('Invalid QoS level. Must be 0, 1, or 2.');
            return self::FAILURE;
        }

        try {
            $mqtt = LaravelMqtt::connection();
            $this->info("Connecting to MQTT broker...");

            $mqtt->connect();
            $this->info("Connected successfully. Subscribing to topic: {$topic}");

            $mqtt->subscribe($topic, function (string $topic, string $message) use ($mqtt, $qos, $retain) {
                $this->processMessage($mqtt, $topic, $message, $qos, $retain);
            }, $qos);

            $this->info("Listening for messages. Press Ctrl+C to exit.");

            while ($mqtt->isConnected()) {
                $mqtt->loop(true);
            }

            $this->info("Disconnected from MQTT broker.");
        } catch (ProtocolNotSupportedException $e) {
            $this->error("Protocol not supported: " . $e->getMessage());
            return self::FAILURE;
        } catch (RepositoryException $e) {
            $this->error("Repository error: " . $e->getMessage());
            return self::FAILURE;
        } catch (DataTransferException $e) {
            $this->error("Data transfer error: " . $e->getMessage());
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("An unexpected error occurred: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Process incoming MQTT message
     *
     * @param mixed $mqtt
     * @param string $topic
     * @param string $message
     * @param int $qos
     * @param bool $retain
     */
    protected function processMessage($mqtt, string $topic, string $message, int $qos, bool $retain): void
    {
        try {
            $this->info("\nReceived message on topic [{$topic}]:");
            $this->line($message);

            // Decode JSON message if possible
            $decoded = json_decode($message, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->info("Decoded JSON:");
                $this->line(json_encode($decoded, JSON_PRETTY_PRINT));
            }

            // Prepare response based on protocol requirements
            $response = [
                "SN" => "4281397465",
                "resource" => "commands/result",
                "status" => [
                    "commandid" => $decoded['commandid'] ?? 0,
                    "result" => 0,
                    "message" => "OK"
                ],
                "data" => $decoded['data'] ?? []
            ];

            // Publish response to appropriate topic
            $responseTopic = $decoded['resource'] . '/result' ?? 'default/response';
            $this->info("Publishing response to topic: {$responseTopic}");

            $mqtt->publish(
                $responseTopic,
                json_encode($response),
                $qos,
                $retain
            );
        } catch (\Exception $e) {
            $this->error("Error processing message: " . $e->getMessage());
        }
    }
}
