<?php

namespace Sydante\LaravelSensitive\Commands;

use Illuminate\Console\Command;
use Sydante\LaravelSensitive\Exceptions\CacheException;
use Sydante\LaravelSensitive\Sensitive;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensitive:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear sensitive trie tree map cache';

    /**
     * Execute the console command.
     *
     * @param Sensitive $sensitive
     *
     * @return void
     * @throws CacheException
     */
    public function handle(Sensitive $sensitive): void
    {
        if ($sensitive->clearCache()) {
            $this->info('Clear cache success.');
        } else {
            $this->error('Please configure the cache value to true!');
        }
    }
}
