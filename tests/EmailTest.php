<?php

namespace Tests;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;

/**
 * Class EmailTest.
 */
class EmailTest extends TestCase
{
    public function testEmail(): void
    {
        $mock = new MockHandler();
        $mock->append(new Result(['success' => true]));

        config([
            'aws.handler' => $mock
        ]);

        $this
            ->post('/email', [
                'from' => 'example@example.com',
                'subject' => 'Hello',
                'message' => 'Hello I am an email'
            ])
            ->seeStatusCode(202)
            ->seeJsonEquals([
                'success' => 'Your email has been sent.',
            ]);
    }

    public function testMissingFields(): void
    {
        $this
            ->post('/email', [])
            ->seeStatusCode(422)
            ->seeJsonEquals([
                'from' => [
                    'Please enter a from address.'
                ],
                'subject' => [
                    'Please enter a subject.'
                ],
                'message' => [
                    'Please enter a message.'
                ]
            ]);
    }

    public function testInvalidFields(): void
    {
        $this
            ->post('/email', [
                'from' => 'invalid',
                'subject' => true,
                'message' => 6
            ])
            ->seeStatusCode(422)
            ->seeJsonEquals([
                'from' => [
                    'Please enter a valid from address.'
                ],
                'subject' => [
                    'Please enter a valid subject.'
                ],
                'message' => [
                    'Please enter a valid message.'
                ]
            ]);
    }

    public function testDynamoError(): void
    {
        $mock = new MockHandler();
        $mock->append(function (CommandInterface $cmd, RequestInterface $req) {
            return new AwsException('Mock exception', $cmd, ['message' => 'DynamoDB Error.']);
        });

        config([
            'aws.handler' => $mock
        ]);

        $testLogHandler = $this->getLogMock();

        $this
            ->post('/email', [
                'from' => 'example@example.com',
                'subject' => 'Hello',
                'message' => 'Hello I am an email'
            ])
            ->seeStatusCode(500)
            ->seeJsonEquals([
                'error' => 'There was a problem sending your email. Please try again later.',
            ]);

        $this->assertTrue($testLogHandler->hasError('Error storing email to dynamo'));
        $this->assertTrue($testLogHandler->hasError('DynamoDB Error.'));
    }
}
