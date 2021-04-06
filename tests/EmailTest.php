<?php

namespace Tests;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Handler\MockHandler as GuzzleMockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

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

        // Create a mock and queue two responses.
        $mock = new GuzzleMockHandler([
            new Response(202, ['Content-Length' => 0], json_encode(['success' => true]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        app()->instance(Client::class, $client);

        $this
            ->post('/email', [
                'name' => 'Test',
                'from' => 'example@example.com',
                'subject' => 'Hello',
                'message' => 'Hello I am an email',
                'recaptcha' => 'recaptcha_string'
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
                'name' => [
                    'Please enter your name.'
                ],
                'from' => [
                    'Please enter a from address.'
                ],
                'subject' => [
                    'Please enter a subject.'
                ],
                'message' => [
                    'Please enter a message.'
                ],
                'recaptcha' => [
                    'Please complete the captcha.'
                ]
            ]);
    }

    public function testInvalidFields(): void
    {
        // Create a mock and queue two responses.
        $mock = new GuzzleMockHandler([
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        app()->instance(Client::class, $client);

        $this
            ->post('/email', [
                'name' => 12.4,
                'from' => 'invalid',
                'subject' => true,
                'message' => 6,
                'recaptcha' => 'invalid'
            ])
            ->seeStatusCode(422)
            ->seeJsonEquals([
                'name' => [
                  'Please enter your name.'
                ],
                'from' => [
                    'Please enter a valid from address.'
                ],
                'subject' => [
                    'Please enter a valid subject.'
                ],
                'message' => [
                    'Please enter a valid message.'
                ],
                'recaptcha' => [
                    'The recaptcha is invalid.'
                ]
            ]);
    }

    public function testRecaptchaFalse(): void
    {
        // Create a mock and queue two responses.
        $mock = new GuzzleMockHandler([
            new Response(202, ['Content-Length' => 0], json_encode(['success' => false]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        app()->instance(Client::class, $client);

        $this
            ->post('/email', [
                'name' => 12.4,
                'from' => 'invalid',
                'subject' => true,
                'message' => 6,
                'recaptcha' => 'invalid'
            ])
            ->seeStatusCode(422)
            ->seeJsonEquals([
                'name' => [
                    'Please enter your name.'
                ],
                'from' => [
                    'Please enter a valid from address.'
                ],
                'subject' => [
                    'Please enter a valid subject.'
                ],
                'message' => [
                    'Please enter a valid message.'
                ],
                'recaptcha' => [
                    'The recaptcha is invalid.'
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

        // Create a mock and queue two responses.
        $mock = new GuzzleMockHandler([
            new Response(202, ['Content-Length' => 0], json_encode(['success' => true]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        app()->instance(Client::class, $client);

        $this
            ->post('/email', [
                'name' => 'Test',
                'from' => 'example@example.com',
                'subject' => 'Hello',
                'message' => 'Hello I am an email',
                'recaptcha' => 'recaptcha_string'
            ])
            ->seeStatusCode(500)
            ->seeJsonEquals([
                'error' => 'There was a problem sending your email. Please try again later.',
            ]);

        $this->assertTrue($testLogHandler->hasError('Error storing email to dynamo'));
        $this->assertTrue($testLogHandler->hasError('DynamoDB Error.'));
    }
}
