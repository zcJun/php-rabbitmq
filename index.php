<?php

require_once __DIR__ . '/RabbitMQTool.php';

// 示例用法

$host = 'localhost';
$port = 5672;
$username = 'guest';
$password = 'guest';

$tool = new RabbitMQTool($host, $port, $username, $password);

////1.简单队列消息
//$queueName = 'simple_queue';
//$tool->sendSimpleMessage($queueName, 'Hello, RabbitMQ1!');

////2.发送工作队列消息
//$queueName = 'work_queue';
//$tool->sendWorkQueueMessage($queueName, 'Hello, RabbitMQ2!');

////3.发送发布订阅消息
//$exchangeName = 'logs';
//$tool->sendPublishSubscribeMessage($exchangeName, 'Hello, RabbitMQ3!');

////4.发送路由消息
//$exchangeName = 'direct_logs';
//$routingKey = 'error';
//$tool->sendRoutingMessage($exchangeName, $routingKey, 'Hello, RabbitMQ4!');

////5.发送主题消息
//$exchangeName = 'topic_logs';
//$routingKey = 'error.*';
//$tool->sendTopicMessage($exchangeName, $routingKey, 'Hello, RabbitMQ5!');




//6.发送延时消息
$queueName = 'delayed_queue';
$message = 'Hello, RabbitMQ6!';
//$delay = 2000; // 5 秒延迟
$delay = 5000; //
//$delay = 10000;
$tool->sendDelayedMessage($queueName, $message, $delay);

