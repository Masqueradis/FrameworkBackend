<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class GenerateDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Markdown-docs about API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating Docs');

        $exitCode = $this->call('l5-swagger:generate');

        if ($exitCode === 0) {
            $this->info('Docs generated successfully');
            return Command::SUCCESS;
        }
        $this->error('Something went wrong');
        return Command::FAILURE;
    }
}
