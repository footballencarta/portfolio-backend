<?php

namespace Tests;

/**
 * Class EmailTest.
 */
class EmailTest extends TestCase
{
    public function testEmail(): void
    {
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
}
