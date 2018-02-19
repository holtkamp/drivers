<?php

namespace Bernard\Driver\QueueInterop\Tests;

use Bernard\Driver\QueueInterop\Driver;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PsrContext|ObjectProphecy
     */
    private $context;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp()
    {
        $this->context = $this->prophesize(PsrContext::class);

        $this->driver = new Driver($this->context->reveal());
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
    public function it_pushes_a_message()
    {
        $queue   = $this->prophesize(PsrQueue::class);
        $message = $this->prophesize(PsrMessage::class);

        $producer = $this->prophesize(PsrProducer::class);
        $producer->send($queue, $message)->shouldBeCalled();

        $this->context->createQueue('send-newsletter')->willReturn($queue);
        $this->context->createMessage('message')->willReturn($message);
        $this->context->createProducer()->willReturn($producer);

        $this->driver->pushMessage('send-newsletter', 'message');
    }

    /**
     * @test
     */
    public function it_pops_messages()
    {
        $queue   = $this->prophesize(PsrQueue::class);
        $message = $this->prophesize(PsrMessage::class);
        $message->getBody()->willReturn('message');

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->receive(6789)->willReturn($message);

        $this->context->createQueue('send-newsletter')->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->assertSame(
            ['message', $message->reveal()],
            $this->driver->popMessage('send-newsletter', 6.789)
        );
    }

    public function it_acknowledges_a_message()
    {
        $queue   = $this->prophesize(PsrQueue::class);
        $message = $this->prophesize(PsrMessage::class);

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->acknowledge($message)->willReturn($message);

        $this->context->createQueue('send-newsletter')->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->driver->acknowledgeMessage('send-newsletter', $message);
    }

    /**
     * @test
     */
    public function it_peeks_in_a_queue()
    {
        $this->assertEquals([], $this->driver->peekQueue('my-queue2'));
    }
}
