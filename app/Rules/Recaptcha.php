<?php

declare(strict_types=1);

namespace App\Rules;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class Recaptcha
 *
 * @package App\Rules
 */
class Recaptcha implements Rule
{
    private Client $client;

    /**
     * Recaptcha constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            // Validate recaptcha
            $response = $this->client->post(env('RECAPTCHA_URL'), [
                'form_params' => [
                    'secret' => env('RECAPTCHA_SECRET'),
                    'response' => $value
                ]
            ]);

            $captchaResponse = json_decode($response->getBody()->getContents(), true);

            return !!$captchaResponse['success'];
        } catch (GuzzleException $e) {
            // There was some problem calling the Google API, log and return false
            app('log')->error('Error calling recaptcha API');
            app('log')->error($e->getMessage());

            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
