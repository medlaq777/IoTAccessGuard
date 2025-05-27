<?php

namespace App\Console\Command;

use App\Services\MqttService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MqttSubscribe extends Command
{
    protected static $defaultName = 'mqtt:subscribe';
    private $mqttService;

    public function __construct(MqttService $mqttService)
    {
        parent::__construct();
        $this->mqttService = $mqttService;
    }

    protected function configure()
    {
        $this->setDescription('Subscribes to MQTT topics and handles incoming messages.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->mqttService->subscribe('test', function ($topic, $message) use ($output) {
            $output->writeln("Received message on topic {$topic}: {$message}");
        });

        $output->writeln('Subscribed to topic. Waiting for messages...');
        
        // Use the $input variable to check for an exit condition
        $exitCondition = $input->getOption('exit-after') ?? 10; // Example: Exit after 10 seconds
        $startTime = time();

        while (true) {
            if ((time() - $startTime) >= $exitCondition) {
                $output->writeln('Exiting after timeout.');
                break;
            }
            sleep(1); // Sleep to prevent busy waiting
        }

        return self::SUCCESS;
    }
}