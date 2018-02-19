<?php

namespace Bernard\Driver\IronMQ\Tests;

use Bernard\Driver\IronMQ\Driver;
use IronMQ\IronMQ;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IronMQ|ObjectProphecy
     */
    private $ironmq;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp()
    {
        $this->ironmq = $this->prophesize(IronMQ::class);

        $this->driver = new Driver($this->ironmq->reveal());
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
        $queues = [
            'failed',
            'queue1',
        ];

        $this->ironmq->getQueues(0, 100)->willReturn([
            (object) ['name' => 'failed'],
            (object) ['name' => 'queue1'],
        ]);

        $this->assertEquals($queues, $this->driver->listQueues());
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->ironmq->getQueue('send-newsletter')->willReturn((object) ['size' => 4]);
        $this->ironmq->getQueue('non-existent')->willReturn(null);

        $this->assertEquals(4, $this->driver->countMessages('send-newsletter'));
        $this->assertEquals(0, $this->driver->countMessages('non-existent'));
    }

    /**
     * @test
     */
    public function it_pushes_a_message()
    {
        $this->ironmq->postMessage('my-queue', 'This is a message')->shouldBeCalled();

        $this->driver->pushMessage('my-queue', 'This is a message');
    }

    /**
     * @test
     */
    public function it_pops_messages()
    {
        $this->ironmq->reserveMessages('my-queue1', 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => 'message1', 'id' => 1],
        ]);

        $this->ironmq->reserveMessages('my-queue2', 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => 'message2', 'id' => 2],
        ]);

        $this->ironmq->reserveMessages('my-queue3', 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn(null);

        $this->assertEquals(['message1', 1], $this->driver->popMessage('my-queue1'));
        $this->assertEquals(['message2', 2], $this->driver->popMessage('my-queue2'));
        $this->assertEquals([null, null], $this->driver->popMessage('my-queue3'));
    }

    /**
     * @test
     */
    public function it_prefetches_messages()
    {
        $this->ironmq->reserveMessages('send-newsletter', 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => 'message1', 'id' => 1],
            (object) ['body' => 'message2', 'id' => 2],
        ]);

        $this->assertEquals(['message1', 1], $this->driver->popMessage('send-newsletter'));
        $this->assertEquals(['message2', 2], $this->driver->popMessage('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->ironmq->deleteMessage('my-queue', 'receipt')->shouldBeCalled();

        $this->driver->acknowledgeMessage('my-queue', 'receipt');
    }

    /**
     * @test
     */
    public function it_peeks_in_a_queue()
    {
        $this->ironmq->peekMessages('my-queue', 10)->willReturn([
            (object) ['body' => 'message1'],
        ]);

        $this->ironmq->peekMessages('my-queue2', 20)->willReturn(null);

        $this->assertEquals(['message1'], $this->driver->peekQueue('my-queue', 10, 10));
        $this->assertEquals([], $this->driver->peekQueue('my-queue2'));
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $this->ironmq->deleteQueue('my-queue')->shouldBeCalled();

        $this->driver->removeQueue('my-queue');
    }

    /**
     * @test
     */
    public function it_exposes_info()
    {
        $driver = new Driver($this->ironmq->reveal(), 10);

        $this->assertEquals(['prefetch' => 10], $driver->info());
        $this->assertEquals(['prefetch' => 2], $this->driver->info());
    }
}
