<?php

namespace Bernard\Driver\QueueInterop\Tests;

use Bernard\Driver\QueueInterop\Driver;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Prophecy\Prophecy\ObjectProphecy;

final class AmqpDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AmqpContext|ObjectProphecy
     */
    private $context;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp()
    {
        $this->context = $this->prophesize(AmqpContext::class);

        $this->driver = new Driver($this->context->reveal());
    }

    /**
     * @test
     */
    public function it_creates_a_queue()
    {
        $queue = $this->prophesize(AmqpQueue::class);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE)->shouldBeCalled();

        $this->context->createQueue('send-newsletter')->willReturn($queue);
        $this->context->declareQueue($queue)->shouldBeCalled();

        $this->driver->createQueue('send-newsletter');
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $queue = $this->prophesize(AmqpQueue::class);

        $this->context->createQueue('send-newsletter')->willReturn($queue);
        $this->context->declareQueue($queue)->willReturn(123);

        $this->assertSame(123, $this->driver->countMessages('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $queue = $this->prophesize(AmqpQueue::class);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE)->shouldBeCalled();

        $this->context->createQueue('send-newsletter')->willReturn($queue);
        $this->context->deleteQueue($queue)->shouldBeCalled();

        $this->driver->removeQueue('send-newsletter');
    }
}
