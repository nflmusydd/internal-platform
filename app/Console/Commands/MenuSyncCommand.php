<?php

namespace App\Console\Commands;

use App\Support\MenuSynchronizer;
use Illuminate\Console\Command;

class MenuSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize the menus table with the master menu catalog (insert / update / delete).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $result = MenuSynchronizer::sync();
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('=========== MENU SYNC RESULT ===========');
        $this->info('Inserted (' . count($result['inserted']) . '):');
        $this->line(implode(', ', $result['inserted']));
        $this->info('Updated (' . count($result['updated']) . '):');
        $this->line(implode(', ', $result['updated']));
        $this->info('Skipped (' . count($result['skipped']) . '):');
        $this->line(implode(', ', $result['skipped']));
        $this->info('Deleted (' . count($result['deleted']) . '):');
        $this->line(implode(', ', $result['deleted']));
        $this->newLine();

        return self::SUCCESS;
    }
}
