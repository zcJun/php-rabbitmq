<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class DelayMq
{
    private $connection;
    private $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
        $this->channel = $this->connection->channel();
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function delayMq($queueName, $message, $delay)
    {
        $args = new AMQPTable([
            'x-delayed-type' => 'direct'
        ]);

        $this->channel->exchange_declare('delay-exchange', 'x-delayed-message', false, true, false, false, false, $args);
        $this->channel->queue_declare($queueName, false, true,false, false, false);
        $this->channel->queue_bind($queueName, 'delay-exchange', 'delay-log-key');

        $msg = new AMQPMessage($message, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable([
                'x-delay' => $delay
            ])
        ]);

        $this->channel->basic_publish($msg, 'delay-exchange', 'delay-log-key');
    }

    public function consumeMq($queueName)
    {
        $callback = function ($msg) {
            echo '输出： ' . $msg->body . PHP_EOL;
            $msg->ack();
        };

        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queueName, '', false, false, false, false, $callback);

        while ($this->channel->is_open()) {
            $this->channel->wait();
        }
    }
}

// 示例用法
$delayMq = new DelayMq();
$delayMq->delayMq('delay-log-queue', 'hi，我是xx，我是通过延迟插件实现的消息延迟推送', 10000);
$delayMq->consumeMq('delay-log-queue');
