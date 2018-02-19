<?php

namespace Bernard\Driver\Pheanstalk\Tests;

use Bernard\Driver\Pheanstalk\Driver;
use Pheanstalk\Job;
use Pheanstalk\PheanstalkInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PheanstalkInterface|ObjectProphecy
     */
    private $pheanstalk;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp()
    {
        $this->pheanstalk = $this->prophesize(PheanstalkInterface::class);

        $this->driver = new Driver($this->pheanstalk->reveal());
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
    public function it_exposes_info()
    {
        $info = new \ArrayObject(['info' => true]);
        $this->pheanstalk->stats()->willReturn($info);

        $this->assertEquals(['info' => true], $this->driver->info());
    }

    /**
     * @test
     */
    public function it_counts_number_of_messages_in_queue()
    {
        $this->pheanstalk->statsTube('send-newsletter')->willReturn(['current-jobs-ready' => 4]);

        $this->assertEquals(4, $this->driver->countMessages('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_list_queues()
    {
        $queues = [
            'failed',
            'queue1',
        ];

        $this->pheanstalk->listTubes()->willReturn($queues);

        $this->assertEquals($queues, $this->driver->listQueues());
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->pheanstalk->delete(Argument::type(Job::class))->shouldBeCalled();

        $this->driver->acknowledgeMessage('my-queue', new Job(1, null));
    }

    /**
     * @test
     */
    public function it_peeks_in_a_queue()
    {
        $this->assertEquals([], $this->driver->peekQueue('my-queue2'));
    }

    /**
     * @test
     */
    public function it_pushes_messages()
    {
        $this->pheanstalk->putInTube('my-queue', 'This is a message')->shouldBeCalled();

        $this->driver->pushMessage('my-queue', 'This is a message');
    }

    /**
     * @test
     */
    public function it_pops_messages()
    {
        $job1 = new Job(1, 'message1');
        $job2 = new Job(2, 'message2');

        $this->pheanstalk->reserveFromTube(Argument::containingString('my-queue'), 5)->will(function ($args) use ($job1, $job2) {
            switch ($args[0]) {
                case 'my-queue1':
                    return $job1;

                case 'my-queue2':
                    return $job2;

                default:
                    return null;
            }
        });

        $this->assertEquals(['message1', $job1], $this->driver->popMessage('my-queue1'));
        $this->assertEquals(['message2', $job2], $this->driver->popMessage('my-queue2'));
        $this->assertEquals([null, null], $this->driver->popMessage('my-queue3'));
    }
}
