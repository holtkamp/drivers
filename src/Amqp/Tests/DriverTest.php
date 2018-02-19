<?php

namespace Bernard\Driver\Amqp\Tests;

use PhpAmqpLib\Channel\AMQPChannel;
use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractConnection|ObjectProphecy
     */
    private $amqp;

    /**
     * @var AMQPChannel|ObjectProphecy
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
        $this->channel = $this->prophesize(AMQPChannel::class);
        $this->channel->close()->willReturn(null);

        $this->amqp = $this->prophesize(AbstractConnection::class);
        $this->amqp->channel()->willReturn($this->channel);

        $this->driver = new Driver($this->amqp->reveal(), $this->exchange = 'exchange');
    }

    /**
     * @test
     */
    public function it_is_a_driver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    /**
     * @test
     */
    public function it_lists_queues()
    {
        $this->assertEquals([], $this->driver->listQueues());
    }

    /**
     * @test
     */
    public function it_creates_a_queue()
    {
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false)->shouldBeCalled();
        $this->channel->queue_declare('send-newsletter', false, true, false, false)->shouldBeCalled();
        $this->channel->queue_bind('send-newsletter', $this->exchange, 'send-newsletter')->shouldBeCalled();

        $this->driver->createQueue('send-newsletter');
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->channel->queue_declare('send-newsletter', true)->willReturn([null, 4]);

        $this->assertEquals(4, $this->driver->countMessages('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_pushes_a_message()
    {
        $driver = new Driver($this->amqp->reveal(), $this->exchange, ['param' => 'value']);

        $amqpMessage = new AMQPMessage('This is a message', ['param' => 'value']);

        $this->channel->basic_publish($amqpMessage, $this->exchange, 'my-queue')->shouldBeCalled();

        $driver->pushMessage('my-queue', 'This is a message');
    }

    /**
     * @test
     */
    public function it_pops_messages()
    {
        $amqpMessage = new AMQPMessage('bar');
        $amqpMessage->delivery_info['delivery_tag'] = 'alright';

        $this->channel->basic_get('my-queue1')->willReturn($amqpMessage);
        $this->channel->basic_get('my-queue2')->willReturn(null);

        $this->assertEquals(['bar', 'alright'], $this->driver->popMessage('my-queue1'));
        $this->assertEquals([null, null], $this->driver->popMessage('my-queue2'));
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->channel->basic_ack('receipt')->shouldBeCalled();

        $this->driver->acknowledgeMessage('my-queue', 'receipt');
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $this->channel->queue_delete('my-queue')->shouldBeCalled();

        $this->driver->removeQueue('my-queue');
    }
}
