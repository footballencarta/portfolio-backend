<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Ses\SesClient;
use Illuminate\Console\Command;

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

    public function handle(Marshaler $marshaler): void
    {
        $event = $this->argument('event');

        if (!is_array($event)) {
            return;
        }

        foreach ($event['Records'] as $record) {
            if ($record['eventName'] !== 'INSERT') {
                app('log')->info('Found ' . $record['eventName'] . '. Skipping...');
                continue;
            }

            $newData = $record['dynamodb']['NewImage'];

            if (!array_key_exists('content', $newData)) {
                app('log')->info('Found ' . $record['eventName'] . '. Skipping...');
                continue;
            }

            $content = decrypt($newData['content']['S']);

            $emailDetails = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $message = '<p>From: ' . $emailDetails['from'] . '</p>' .
                       '<p><b>Message:</b><br />' . $emailDetails['message'] . '</p>';

            /** @var SesClient $ses */
            $ses = app('aws')->createClient('ses');

            $ses->sendEmail([
                'Destination' => [
                    'ToAddresses' => [
                        'damon@damonwilliams.co.uk'
                    ]
                ],
                'Source' => 'website@damonwilliams.co.uk',
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'utf8',
                            'Data' => $message
                        ],
                        'Text' => [
                            'Charset' => 'utf8',
                            'Data' => strip_tags($message)
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'utf8',
                        'Data' => $emailDetails['subject']
                    ],
                ]
            ]);

            $key = $marshaler->marshalJson(json_encode([
                'id' => $newData['id']['S']
            ], JSON_THROW_ON_ERROR));

            $eav = $marshaler->marshalJson(json_encode([
                ':s' => 'Sent'
            ], JSON_THROW_ON_ERROR));

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

            $result = $dynamo->updateItem($params);

            echo "Updated item.\n";

            print_r($result['Attributes']);
        }
    }
}
