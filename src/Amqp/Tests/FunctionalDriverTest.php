<?php

namespace Bernard\Driver\Amqp\Tests;

use PhpAmqpLib\Channel\AMQPChannel;
use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @group functional
 */
final class FunctionalDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AMQPStreamConnection
     */
    private $amqp;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var string
     */
    private $exchange;

    public function setUp()
    {
        $this->amqp = new AMQPStreamConnection($_ENV['RABBITMQ_HOST'], $_ENV['RABBITMQ_PORT'], 'guest', 'guest');
        $this->channel = $this->amqp->channel();

        $this->driver = new Driver($this->amqp, $this->exchange = 'exchange');
    }

    public function tearDown()
    {
        try {
            $this->channel->queue_delete('send-newsletter');
        } catch (AMQPRuntimeException $e) {
        }

        $this->channel->close();
    }

    /**
     * @test
     */
    public function it_creates_a_queue()
    {
        $this->driver->createQueue('send-newsletter');

        $amqpMessage = new AMQPMessage('This is a message');
        $this->channel->basic_publish($amqpMessage, $this->exchange, 'send-newsletter');

        /** @var AMQPMessage $returnedMessage */
        $returnedMessage = $this->channel->basic_get('send-newsletter');

        $this->assertEquals($amqpMessage->body, $returnedMessage->body);
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->createQueue('send-newsletter');

        $amqpMessage = new AMQPMessage('This is a message');
        $this->channel->basic_publish($amqpMessage, $this->exchange, 'send-newsletter');

        $amqpMessage = new AMQPMessage('This is another message');
        $this->channel->basic_publish($amqpMessage, $this->exchange, 'send-newsletter');

        // TODO: find out why things are slow on travis
        sleep(1);

        $this->assertEquals(2, $this->driver->countMessages('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_pushes_a_message()
    {
        $this->createQueue('send-newsletter');

        $this->driver->pushMessage('send-newsletter', 'This is a message');

        // TODO: find out why things are slow on travis
        sleep(1);

        /** @var AMQPMessage $returnedMessage */
        $returnedMessage = $this->channel->basic_get('send-newsletter');

        $this->assertEquals('This is a message', $returnedMessage->body);
    }

    /**
     * @test
     */
    public function it_pushes_a_message_with_properties()
    {
        $this->createQueue('send-newsletter');

        $driver = new Driver($this->amqp, $this->exchange, ['content_type' => 'text']);

        $driver->pushMessage('send-newsletter', 'This is a message');

        // TODO: find out why things are slow on travis
        sleep(1);

        /** @var AMQPMessage $returnedMessage */
        $returnedMessage = $this->channel->basic_get('send-newsletter');

        $this->assertEquals('This is a message', $returnedMessage->body);
        $this->assertEquals(['content_type' => 'text'], $returnedMessage->get_properties());
    }

    /**
     * @test
     */
    public function it_pops_messages()
    {
        $this->createQueue('send-newsletter');

        $amqpMessage = new AMQPMessage('This is a message');
        $this->channel->basic_publish($amqpMessage, $this->exchange, 'send-newsletter');

        $this->assertEquals(['This is a message', '1'], $this->driver->popMessage('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->createQueue('send-newsletter');

        $amqpMessage = new AMQPMessage('This is a message');
        $this->channel->basic_publish($amqpMessage, $this->exchange, 'send-newsletter');

        /** @var AMQPMessage $returnedMessage */
        $returnedMessage = $this->channel->basic_get('send-newsletter');

        $this->driver->acknowledgeMessage('send-newsletter', $returnedMessage->delivery_info['delivery_tag']);

        // No messages remained in the queue
        $result = $this->channel->queue_purge('send-newsletter');
        $this->assertEquals(0, $result);
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $this->createQueue('send-newsletter');

        $this->driver->removeQueue('send-newsletter');

        $amqpMessage = new AMQPMessage('This is a message');
        $this->channel->basic_publish($amqpMessage, $this->exchange, 'send-newsletter');

        $this->expectException(AMQPProtocolException::class);
        $this->expectExceptionMessage(sprintf("NOT_FOUND - no queue '%s' in vhost '/'", 'send-newsletter'));

        $this->channel->basic_get('send-newsletter');
    }

    /**
     * Creates a new queue.
     *
     * @param $queueName
     */
    private function createQueue($queueName)
    {
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $this->exchange, $queueName);
    }
}
