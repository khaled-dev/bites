<?php

namespace App\Services\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqService implements MessagingContract
{
    public function publish(array $data): void
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost'),
        );

        $channel = $connection->channel();

        // Declare the queue (must match RabbitMQ config)
        $channel->queue_declare(config('rabbitmq.queue'), false, true, false, false);

        $msg = new AMQPMessage(json_encode($data));
        $channel->basic_publish($msg, '', 'file-events');

        $channel->close();
        $connection->close();
    }
}
