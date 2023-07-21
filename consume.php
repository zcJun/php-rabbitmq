<?php

require_once __DIR__ . '/RabbitMQTool.php';

// 示例用法

$host = 'localhost';
$port = 5672;
$username = 'guest';
$password = 'guest';

$tool = new RabbitMQTool($host, $port, $username, $password);

////1.消费简单队列消息
//$queueName = 'simple_queue';
//$tool->consumeSimpleMessage($queueName, function ($message) {
//    var_dump($message->body);
//});

////2.消费工作队列消息
//$queueName = 'work_queue';
//$tool->consumeWorkQueueMessage($queueName, function ($message) {
//    var_dump($message->body);
//    // 手动确认消息
//    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
//});

////3.消费发布订阅消息
//$exchangeName = 'logs';
//$tool->consumePublishSubscribeMessage($exchangeName, function ($message) {
//    var_dump($message->body);
//});

////4.消费路由消息
//$exchangeName = 'direct_logs';
//$routingKey = 'error';
//$tool->consumeRoutingMessage($exchangeName, $routingKey, function ($message) {
//    var_dump($message->body);
//});

////5.消费主题消息
//$exchangeName = 'topic_logs';
//$routingKey = 'error.*';
//$tool->consumeTopicMessage($exchangeName, $routingKey, function ($message) {
//    var_dump($message->getBody());
//});



//6.消费延时队列消息
$queueName = 'delayed_queue';
// 消费延时消息
$tool->consumeDelayedMessage($queueName, function ($message) {
    var_dump($message->body);
    $message->ack();
});
