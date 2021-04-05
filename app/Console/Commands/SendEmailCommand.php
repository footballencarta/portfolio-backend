<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Illuminate\Console\Command;
use JsonException;

/**
 * Class SendEmailCommand
 *
 * @package App\Console\Commands
 */
class SendEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-email {event}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends an email from dynamo';

    protected Marshaler $marshaler;

    /**
     * Handles sending an email
     *
     * @param Marshaler $marshaler
     *
     * @throws JsonException
     */
    public function handle(Marshaler $marshaler): void
    {
        $event = $this->argument('event');

        if (!is_array($event)) {
            app('log')->info('No event found. Skipping...');
            return;
        }

        $this->marshaler = $marshaler;

        foreach ($event['Records'] as $record) {
            if ($record['eventName'] !== 'INSERT') {
                app('log')->info('Found ' . $record['eventName'] . '. Skipping...');
                continue;
            }

            $newData = $record['dynamodb']['NewImage'];

            if (!array_key_exists('content', $newData)) {
                app('log')->info('No content found. Skipping...');
                continue;
            }

            $emailId = $newData['id']['S'];
            $content = decrypt($newData['content']['S']);
            $emailDetails = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            app('log')->info(sprintf(
                'Found email %s.',
                $emailId
            ));

            $hasSentSuccessfully = $this->sendEmail($emailDetails);

            $newStatus = ($hasSentSuccessfully) ? 'Sent' : 'Failed';

            $this->updateStatus($emailId, $newStatus);
        }
    }

    /**
     * Returns the body of the email
     *
     * @param array $emailDetails
     *
     * @return array
     */
    protected function buildMessage(array $emailDetails): array
    {
        $htmlMessage = <<<MSG
<p>From: {$emailDetails['from']}</p>

<p><b>Message:</b>
<br />{$emailDetails['message']}</p>
MSG;

        $plainTextMessage = strip_tags($htmlMessage);

        app('log')->info('Generated email content.');
        app('log')->info($htmlMessage);
        app('log')->info($plainTextMessage);

        return [
            $htmlMessage,
            $plainTextMessage
        ];
    }

    /**
     * Sends the email via SES
     *
     * @param array $emailDetails
     *
     * @return bool
     */
    protected function sendEmail(array $emailDetails): bool
    {
        [$htmlMessage, $plainTextMessage] = $this->buildMessage($emailDetails);

        /** @var SesClient $ses */
        $ses = app('aws')->createClient('ses');

        try {
            $ses->sendEmail([
                'Destination' => [
                    'ToAddresses' => [
                        env('DESTINATION_ADDRESS')
                    ]
                ],
                'Source' => env('SENDER_ADDRESS'),
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'utf8',
                            'Data' => $htmlMessage
                        ],
                        'Text' => [
                            'Charset' => 'utf8',
                            'Data' => $plainTextMessage
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'utf8',
                        'Data' => $emailDetails['subject']
                    ],
                ]
            ]);

            return true;
        } catch (AwsException $e) {
            // Log error, then return false to indicate failure
            app('log')->error('AWS Error on sending email.');
            app('log')->error(strval($e->getAwsErrorMessage()));

            return false;
        }
    }

    /**
     * Updates DynamoDB with the new status of the email
     *
     * @param string $emailId
     * @param string $newStatus
     *
     * @return bool
     *
     * @throws JsonException
     */
    protected function updateStatus(string $emailId, string $newStatus): bool
    {
        try {
            $key = $this->marshalItem([
                'id' => $emailId
            ]);

            $eav = $this->marshalItem([
                ':s' => $newStatus
            ]);

            $ean = [
                '#field' => 'status'
            ];

            $params = [
                'TableName' => env('DYNAMODB_EMAIL_TABLE_NAME'),
                'Key' => $key,
                'UpdateExpression' => 'set #field = :s',
                'ExpressionAttributeValues' => $eav,
                'ExpressionAttributeNames' => $ean,
                'ReturnValues' => 'UPDATED_NEW'
            ];

            /** @var DynamoDbClient $dynamo */
            $dynamo = app('aws')->createClient('dynamodb');

            $dynamo->updateItem($params);

            app('log')->info(sprintf(
                'Email %s has been updated to %s.',
                $emailId,
                $newStatus
            ));

            return true;
        } catch (AwsException $e) {
            // Log error, then return false to indicate failure
            app('log')->error('AWS Error on updating status.');
            app('log')->error(strval($e->getAwsErrorMessage()));

            /**
             * !!THERE IS A PROBLEM HERE!!
             *
             * If we get to this point, we've sent the email, but not recorded the new status.
             *
             * Unfortunately, as we can't transact with Dynamo similar to how we do with traditional DBMS systems,
             * we'll never know this email was actually sent _unless_ we start using SES Configuration Sets to track
             * delivery. I've chosen to make this out of scope for this portfolio website.
             */

            return false;
        }
    }

    /**
     * Marshals an item for DynamoDB
     *
     * @param array $item
     *
     * @return array
     *
     * @throws JsonException
     */
    protected function marshalItem(array $item): array
    {
        return $this->marshaler->marshalJson(json_encode($item, JSON_THROW_ON_ERROR));
    }
}
