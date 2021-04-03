<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\SendEmailCommand;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @package App\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendEmailCommand::class
    ];
}
