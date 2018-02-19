<?php

namespace Bernard\Driver\Pheanstalk\Tests;

use Bernard\Driver\Pheanstalk\Driver;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;

/**
 * @group functional
 */
final class FunctionalDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp()
    {
        $this->pheanstalk = new Pheanstalk($_ENV['BEANSTALKD_HOST'], $_ENV['BEANSTALKD_PORT']);

        $this->driver = new Driver($this->pheanstalk);
    }

    public function tearDown()
    {
        $tubes = $this->pheanstalk->listTubes();

        foreach ($tubes as $tube) {
            while (true) {
                try {
                    $next = $this->pheanstalk->peekReady($tube);
                } catch (\Exception $e) {
                    break;
                }

                $this->pheanstalk->delete($next);
            }
        }
    }

    /**
     * @test
     */
    public function it_lists_queues()
    {
        $this->pheanstalk->putInTube('list', 'message');

        $queues = $this->driver->listQueues();

        $this->assertContains('default', $queues);
        $this->assertContains('list', $queues);
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->pheanstalk->putInTube('count', 'message');
        $this->pheanstalk->putInTube('count', 'message');
        $this->pheanstalk->putInTube('count', 'message');
        $this->pheanstalk->putInTube('count', 'message');

        $this->assertEquals(4, $this->driver->countMessages('count'));
    }

    /**
     * @test
     */
    public function it_pushes_a_message()
    {
        $this->driver->pushMessage('push', 'This is a message');

        $job = $this->pheanstalk->peekReady('push');

        $this->assertEquals('This is a message', $job->getData());
    }

    /**
     * @test
     */
    public function it_pops_messages()
    {
        $this->pheanstalk->putInTube('pop', 'message');

        $message = $this->driver->popMessage('pop');

        $this->assertEquals('message', $message[0]);
        $this->assertEquals('message', $message[1]->getData());
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->pheanstalk->putInTube('ack', 'message');
        $job = $this->pheanstalk->reserveFromTube('ack', 2);

        $this->driver->acknowledgeMessage('ack', $job);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(sprintf('NOT_FOUND: Job %d does not exist.', $job->getId()));

        $this->pheanstalk->peek($job->getId());
    }

    /**
     * @test
     */
    public function it_exposes_info()
    {
        $info = $this->driver->info();

        // Some known pheanstalk metrics
        $this->assertArrayHasKey('current-jobs-urgent', $info);
        $this->assertArrayHasKey('current-jobs-ready', $info);
        $this->assertArrayHasKey('current-jobs-reserved', $info);
        $this->assertArrayHasKey('current-jobs-delayed', $info);
        $this->assertArrayHasKey('current-jobs-buried', $info);
    }
}
