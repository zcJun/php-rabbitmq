<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class RabbitMQTool
 * 该代码是一个RabbitMQ工具类，用于发送和接收消息。
 * 它提供了不同类型的消息传递模式，包括简单队列、工作队列、发布订阅、路由和主题。您可以使用该工具类与RabbitMQ进行交互。
 */

class RabbitMQTool
{
    private static $instance; // 单例实例
    private $connection; // RabbitMQ连接对象
    private $channel; // RabbitMQ通道对象

    public function __construct($host, $port, $username, $password)
    {
        try {
            $this->connection = new AMQPStreamConnection($host, $port, $username, $password); // 创建RabbitMQ连接
            $this->channel = $this->connection->channel(); // 创建RabbitMQ通道
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

//    //单例模式
//    public static function getInstance($host, $port, $username, $password)
//    {
//        if (!self::$instance) {
//            self::$instance = new self($host, $port, $username, $password);
//        }
//        return self::$instance;
//    }

    public function __destruct()
    {
        try {
            $this->channel->close(); // 关闭RabbitMQ通道
            $this->connection->close(); // 关闭RabbitMQ连接
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 发送简单队列消息
     *
     * @param string $queueName 队列名称
     * @param string $message 消息内容
     */
    public function sendSimpleMessage($queueName, $message)
    {
        try {
            $this->channel->queue_declare($queueName, false, false, false, false); // 声明简单队列
            $msg = new AMQPMessage($message); // 创建消息对象
            $this->channel->basic_publish($msg, '', $queueName); // 发布消息到队列
            echo "消息已发送：$message\n"; // 打印发送的消息
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 消费简单队列消息
     *
     * @param string $queueName 队列名称
     * @param callable $callback 消息处理回调函数
     */
    public function consumeSimpleMessage($queueName, $callback)
    {
        try {
            $this->channel->queue_declare($queueName, false, false, false, false); // 声明简单队列
            $this->channel->basic_consume($queueName, '', false, true, false, false, $callback); // 消费队列中的消息，并指定回调函数处理消息
            while ($this->channel->is_consuming()) {
                $this->channel->wait(); // 持续等待并处理消息
            }
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 发送工作队列消息
     *
     * @param string $queueName 队列名称
     * @param string $message 消息内容
     */
    public function sendWorkQueueMessage($queueName, $message)
    {
        try {
            $this->channel->queue_declare($queueName, false, false, false, false); // 声明工作队列
            $msg = new AMQPMessage($message, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]); // 创建持久化消息对象
            $this->channel->basic_publish($msg, '', $queueName); // 发布持久化消息到队列
            echo "消息已发送：$message\n"; // 打印发送的消息
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 消费工作队列消息
     *
     * @param string $queueName 队列名称
     * @param callable $callback 消息处理回调函数
     */
    public function consumeWorkQueueMessage($queueName, $callback)
    {
        try {
            $this->channel->queue_declare($queueName, false, false, false, false); // 声明工作队列
            $this->channel->basic_qos(null, 1, null); // 设置每次只从队列中获取一条消息
            $this->channel->basic_consume($queueName, '', false, false, false, false, $callback); // 消费队列中的消息，并指定回调函数处理消息
            while ($this->channel->is_consuming()) {
                $this->channel->wait(); // 持续等待并处理消息
            }
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 发送发布订阅消息
     *
     * @param string $exchangeName 交换机名称
     * @param string $message 消息内容
     */
    public function sendPublishSubscribeMessage($exchangeName, $message)
    {
        try {
            $this->channel->exchange_declare($exchangeName, 'fanout', false, false, false); // 声明发布订阅交换机
            $msg = new AMQPMessage($message); // 创建消息对象
            $this->channel->basic_publish($msg, $exchangeName); // 发布消息到交换机
            echo "消息已发送：$message\n"; // 打印发送的消息
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 消费发布订阅消息
     *
     * @param string $exchangeName 交换机名称
     * @param callable $callback 消息处理回调函数
     */
    public function consumePublishSubscribeMessage($exchangeName, $callback)
    {
        try {
            $this->channel->exchange_declare($exchangeName, 'fanout', false, false,false); // 声明发布订阅交换机
            list($queueName, ,) = $this->channel->queue_declare('', false, false, true, false); // 创建临时队列
            $this->channel->queue_bind($queueName, $exchangeName); // 将临时队列绑定到交换机
            $this->channel->basic_consume($queueName, '', false, true, false, false, $callback); // 消费队列中的消息，并指定回调函数处理消息
            while ($this->channel->is_consuming()) {
                $this->channel->wait(); // 持续等待并处理消息
            }
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 发送路由消息
     *
     * @param string $exchangeName 交换机名称
     * @param string $routingKey 路由键
     * @param string $message 消息内容
     */
    public function sendRoutingMessage($exchangeName, $routingKey, $message)
    {
        try {
            $this->channel->exchange_declare($exchangeName, 'direct', false, false, false); // 声明路由交换机
            $msg = new AMQPMessage($message); // 创建消息对象
            $this->channel->basic_publish($msg, $exchangeName, $routingKey); // 发布消息到交换机，并指定路由键
            echo "消息已发送：$message\n"; // 打印发送的消息
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 消费路由消息
     *
     * @param string $exchangeName 交换机名称
     * @param string $routingKey 路由键
     * @param callable $callback 消息处理回调函数
     */
    public function consumeRoutingMessage($exchangeName, $routingKey, $callback)
    {
        try {
            $this->channel->exchange_declare($exchangeName, 'direct', false, false, false); // 声明路由交换机
            list($queueName, ,) = $this->channel->queue_declare('', false, false, true, false); // 创建临时队列
            $this->channel->queue_bind($queueName, $exchangeName, $routingKey); // 将临时队列绑定到交换机，并指定路由键
            $this->channel->basic_consume($queueName, '', false, true,false, false, $callback); // 消费队列中的消息，并指定回调函数处理消息
            while ($this->channel->is_consuming()) {
                $this->channel->wait(); // 持续等待并处理消息
            }
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 发送主题消息
     *
     * @param string $exchangeName 交换机名称
     * @param string $routingKey 路由键
     * @param string $message 消息内容
     */
    public function sendTopicMessage($exchangeName, $routingKey, $message)
    {
        try {
            $this->channel->exchange_declare($exchangeName, 'topic', false, false, false); // 声明主题交换机
            $msg = new AMQPMessage($message); // 创建消息对象
            $this->channel->basic_publish($msg, $exchangeName, $routingKey); // 发布消息到交换机，并指定路由键
            echo "消息已发送：$message\n"; // 打印发送的消息
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 消费主题消息
     *
     * @param string $exchangeName 交换机名称
     * @param string $routingKey 路由键
     * @param callable $callback 消息处理回调函数
     */
    public function consumeTopicMessage($exchangeName, $routingKey, $callback)
    {
        try {
            $this->channel->exchange_declare($exchangeName, 'topic', false, false, false); // 声明主题交换机
            list($queueName, ,) = $this->channel->queue_declare('', false, false, true, false); // 创建临时队列
            $this->channel->queue_bind($queueName, $exchangeName, $routingKey); // 将临时队列绑定到交换机，并指定路由键
            $this->channel->basic_consume($queueName, '', false, true, false, false, $callback); // 消费队列中的消息，并指定回调函数处理消息
            while ($this->channel->is_consuming()) {
                $this->channel->wait(); // 持续等待并处理消息
            }
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 发送延时消息
     *
     * @param string $queueName 队列名称
     * @param string $message 消息内容
     * @param int $delay 延时时间（毫秒）
     */
    public function sendDelayedMessage($queueName, $message, $delay)
    {
        try {
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
            echo "消息已发送：$message\n"; // 打印发送的消息
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }


    /**
     * 消费延时队列消息
     *
     * @param string $queueName 队列名称
     * @param callable $callback 消息处理回调函数
     */
    public function consumeDelayedMessage($queueName, $callback)
    {
        try {

            $this->channel->basic_qos(null, 1, null);
            $this->channel->basic_consume($queueName, '', false, false, false, false, $callback);

            while ($this->channel->is_open()) {
                $this->channel->wait();
            }

        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }
}