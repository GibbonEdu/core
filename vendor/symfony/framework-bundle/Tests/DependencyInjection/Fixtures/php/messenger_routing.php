<?php

$container->loadFromExtension('framework', array(
    'serializer' => true,
    'messenger' => array(
        'serializer' => true,
        'routing' => array(
            'Symfony\Component\Messenger\Tests\Fixtures\DummyMessage' => array('amqp', 'audit'),
            'Symfony\Component\Messenger\Tests\Fixtures\SecondMessage' => array(
                'senders' => array('amqp', 'audit'),
                'send_and_handle' => true,
            ),
            '*' => 'amqp',
        ),
        'transports' => array(
            'amqp' => 'amqp://localhost/%2f/messages',
        ),
    ),
));
