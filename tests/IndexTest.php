<?php

namespace Tests;

/**
 * Class IndexTest.
 */
class IndexTest extends TestCase
{
    public function testIndex(): void
    {
        $this
            ->get('/')
            ->seeStatusCode(200)
            ->seeJsonEquals([
                'response' => true,
            ]);
    }

    public function test404(): void
    {
        $this
            ->get('/404')
            ->seeStatusCode(404);
    }
}
