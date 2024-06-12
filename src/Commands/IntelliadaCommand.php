<?php

namespace Ollico\Intelliada\Commands;

use Illuminate\Console\Command;

class IntelliadaCommand extends Command
{
    public $signature = 'laravel-intelliada';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
