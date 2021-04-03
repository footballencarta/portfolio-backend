<?php

namespace Tests;

use Aws\MockHandler;
use Aws\Result;
use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

/**
 * Class TestCase.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    /**
     * Mocks out Monolog so we can test log output
     *
     * @return TestHandler
     */
    protected function getLogMock(): TestHandler
    {
        $testLogHandler = new TestHandler();

        /** @var Logger $monolog */
        $monolog = app('log');

        $monolog->setHandlers([
            $testLogHandler
        ]);

        return $testLogHandler;
    }
}
