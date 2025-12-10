<?php

namespace App\Console\Commands;

use App\Jobs\DetectCarrierOutages;
use App\Jobs\ProcessDatabaseInputs;
use Illuminate\Console\Command;

class TestCarrierDown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TestCarrierDown';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DetectCarrierOutages::dispatch();
    }
}
