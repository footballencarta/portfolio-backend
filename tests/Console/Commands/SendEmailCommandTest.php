<?php

namespace Tests\Console\Commands;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class SendEmailCommandTest extends TestCase
{
    public function testEmailSend()
    {
        $mock = new MockHandler();
        $mock->append(new Result(['success' => true])); // email send
        $mock->append(new Result(['success' => true])); // dynamo update

        config([
            'aws.handler' => $mock
        ]);

        $testLogHandler = $this->getLogMock();

        $dynamoStream = json_decode(file_get_contents(base_path('tests/Mock/dynamodb.json')), true);

        $this->artisan('send-email', [
            'event' => $dynamoStream
        ]);

        $this->assertTrue($testLogHandler->hasInfo('Found email 1234-abcd-5678-efgh.'));
        $this->assertTrue($testLogHandler->hasInfo('Generated email content.'));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
<p>From: test@example.com</p>

<p><b>Message:</b>
<br />test</p>
MSG));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
From: test@example.com

Message:
test
MSG));
        $this->assertTrue($testLogHandler->hasInfo('Email 1234-abcd-5678-efgh has been updated to Sent.'));
    }

    public function testMultipleEmails()
    {
        $mock = new MockHandler();

        $mock->append(new Result(['success' => true])); // First email send
        $mock->append(new Result(['success' => true])); // First dynamo update

        $mock->append(new Result(['success' => true])); // Second email send
        $mock->append(new Result(['success' => true])); // Second dynamo update

        config([
            'aws.handler' => $mock
        ]);

        $testLogHandler = $this->getLogMock();

        $dynamoStream = json_decode(file_get_contents(base_path('tests/Mock/dynamodb.json')), true);

        $newRecord = $dynamoStream['Records'][0];

        $newRecord['dynamodb']['NewImage']['id']['S'] = 'new-id';

        $newRecord['dynamodb']['NewImage']['content']['S'] = encrypt(json_encode([
            'from' => 'test2@example.com',
            'subject' => 'Test2',
            'message' => 'Test 2'
        ]));

        $dynamoStream['Records'][] = $newRecord;

        $this->artisan('send-email', [
            'event' => $dynamoStream
        ]);

        $this->assertTrue($testLogHandler->hasInfo('Found email 1234-abcd-5678-efgh.'));
        $this->assertTrue($testLogHandler->hasInfo('Generated email content.'));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
<p>From: test@example.com</p>

<p><b>Message:</b>
<br />test</p>
MSG));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
From: test@example.com

Message:
test
MSG));
        $this->assertTrue($testLogHandler->hasInfo('Email 1234-abcd-5678-efgh has been updated to Sent.'));

        $this->assertTrue($testLogHandler->hasInfo('Found email new-id.'));
        $this->assertTrue($testLogHandler->hasInfo('Generated email content.'));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
<p>From: test2@example.com</p>

<p><b>Message:</b>
<br />Test 2</p>
MSG));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
From: test2@example.com

Message:
Test 2
MSG));
        $this->assertTrue($testLogHandler->hasInfo('Email new-id has been updated to Sent.'));
    }

    public function testMissingEvent()
    {
        $testLogHandler = $this->getLogMock();

        $this->artisan('send-email', [
            'event' => 'event'
        ]);

        $this->assertTrue($testLogHandler->hasInfo('No event found. Skipping...'));
    }

    public function testNonInsertEvent()
    {
        $testLogHandler = $this->getLogMock();

        $dynamoStream = json_decode(file_get_contents(base_path('tests/Mock/dynamodb.json')), true);

        $dynamoStream['Records'][0]['eventName'] = 'MODIFY';

        $this->artisan('send-email', [
            'event' => $dynamoStream
        ]);

        $this->assertTrue($testLogHandler->hasInfo('Found MODIFY. Skipping...'));
    }

    public function testMissingContent()
    {
        $testLogHandler = $this->getLogMock();

        $dynamoStream = json_decode(file_get_contents(base_path('tests/Mock/dynamodb.json')), true);

        unset($dynamoStream['Records'][0]['dynamodb']['NewImage']['content']);

        $this->artisan('send-email', [
            'event' => $dynamoStream
        ]);

        $this->assertTrue($testLogHandler->hasInfo('No content found. Skipping...'));
    }

    public function testSesError()
    {
        $mock = new MockHandler();
        $mock->append(function (CommandInterface $cmd, RequestInterface $req) {
            return new AwsException('Mock exception', $cmd, ['message' => 'Email Error.']);
        }); // Email error
        $mock->append(new Result(['success' => true])); // dynamo update

        config([
            'aws.handler' => $mock
        ]);

        $testLogHandler = $this->getLogMock();

        $dynamoStream = json_decode(file_get_contents(base_path('tests/Mock/dynamodb.json')), true);

        $this->artisan('send-email', [
            'event' => $dynamoStream
        ]);

        $this->assertTrue($testLogHandler->hasInfo('Found email 1234-abcd-5678-efgh.'));
        $this->assertTrue($testLogHandler->hasInfo('Generated email content.'));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
<p>From: test@example.com</p>

<p><b>Message:</b>
<br />test</p>
MSG));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
From: test@example.com

Message:
test
MSG));
        $this->assertTrue($testLogHandler->hasError('AWS Error on sending email.'));
        $this->assertTrue($testLogHandler->hasError('Email Error.'));
        $this->assertTrue($testLogHandler->hasInfo('Email 1234-abcd-5678-efgh has been updated to Failed.'));
    }

    public function testDynamoError()
    {
        $mock = new MockHandler();
        $mock->append(new Result(['success' => true])); // Email sent
        $mock->append(function (CommandInterface $cmd, RequestInterface $req) {
            return new AwsException('Mock exception', $cmd, ['message' => 'DynamoDB Error.']);
        }); // Dynamo error

        config([
            'aws.handler' => $mock
        ]);

        $testLogHandler = $this->getLogMock();

        $dynamoStream = json_decode(file_get_contents(base_path('tests/Mock/dynamodb.json')), true);

        $this->artisan('send-email', [
            'event' => $dynamoStream
        ]);

        $this->assertTrue($testLogHandler->hasInfo('Found email 1234-abcd-5678-efgh.'));
        $this->assertTrue($testLogHandler->hasInfo('Generated email content.'));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
<p>From: test@example.com</p>

<p><b>Message:</b>
<br />test</p>
MSG));
        $this->assertTrue($testLogHandler->hasInfo(<<<MSG
From: test@example.com

Message:
test
MSG));
        $this->assertTrue($testLogHandler->hasError('AWS Error on updating status.'));
        $this->assertTrue($testLogHandler->hasError('DynamoDB Error.'));
    }
}
