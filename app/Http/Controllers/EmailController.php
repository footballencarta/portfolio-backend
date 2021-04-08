<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;
use Laravel\Lumen\Routing\Controller;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EmailController
 *
 * @package App\Http\Controllers
 */
class EmailController extends Controller
{
    private Client $client;

    /**
     * EmailController constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    /**
     * Sends an email to SQS to be sent.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ValidationException
     * @throws JsonException
     */
    public function send(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|string',
            'from' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'recaptcha' => [
                'required',
                new Recaptcha($this->client)
            ]
        ], [
            'name.required' => 'Please enter your name.',
            'name.string' => 'Please enter your name.', // Please enter a valid name wouldn't make much sense here...
            'from.required' => 'Please enter a from address.',
            'from.email' => 'Please enter a valid from address.',
            'subject.required' => 'Please enter a subject.',
            'subject.string' => 'Please enter a valid subject.',
            'message.required' => 'Please enter a message.',
            'message.string' => 'Please enter a valid message.',
            'recaptcha.required' => 'Please complete the captcha.',
        ]);

        try {
            /**
             * We don't catch the JsonException here as it's realistically never going to be thrown, as we'll always be
             * encoding valid data due to the validators
             */
            $this->storeInDynamo($request);
        } catch (AwsException $e) {
            /**
             * We catch any AWS Exceptions here to allow us to return an error and log what went wrong
             */
            app('log')->error('Error storing email to dynamo');
            app('log')->error(strval($e->getAwsErrorMessage()));

            return $this->errorSendingEmail();
        }

        return response()->json([
            'success' => 'Your email has been sent.'
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Stores the request information in Dynamo for processing
     *
     * @param Request $request
     *
     * @throws JsonException
     * @throws AwsException
     */
    protected function storeInDynamo(Request $request): void
    {
        /**
         * We're stripping tags here to sanitise the data before we put it into the database.
         */
        $name = strip_tags($request->input('name'));
        $from = strip_tags($request->input('from'));
        $subject = strip_tags($request->input('subject'));
        $message = nl2br(strip_tags($request->input('message')));

        /**
         * Encrypt the sent detail using the APP_KEY env as the email is classedas PII under GDPR, and the other
         * fields _may_ contain PII. Only the APP_KEY is able to decrypt this value.
         */
        $content = encrypt(json_encode([
            'name' => $name,
            'from' => $from,
            'subject' => $subject,
            'message' => $message
        ], JSON_THROW_ON_ERROR));

        /**
         * We're using a time-based UUID here, so we don't have sequential ids
         * being used, so they're harder to sniff.
         */
        $id = Uuid::uuid1();

        /** @var DynamoDbClient $dynamo */
        $dynamo = app('aws')->createClient('dynamodb');

        /**
         * Storing the data in DynamoDB triggers off a DynamoDB Stream that allows
         * us to track the status of sending the email
         */
        $dynamo->putItem([
            'TableName' => env('DYNAMODB_EMAIL_TABLE_NAME'),
            'Item' => [
                'id' => [
                    'S' => $id
                ],
                'content' => [
                    'S' => $content
                ],
                'status' => [
                    'S' => 'Pending'
                ]
            ]
        ]);
    }

    /**
     * Returns the response for errors sending the email
     *
     * @return JsonResponse
     */
    protected function errorSendingEmail(): JsonResponse
    {
        /**
         * HTTP 500 is used here because the problem isn't related to the data
         * the client sent - it's related to the server having a problem
         * processing it
         */
        return response()->json([
            'error' => 'There was a problem sending your email. Please try again later.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
