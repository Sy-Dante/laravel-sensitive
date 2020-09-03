<?php

namespace Sydante\LaravelSensitive\Commands;

use Illuminate\Console\Command;
use Sydante\LaravelSensitive\Exceptions\CacheException;
use Sydante\LaravelSensitive\Exceptions\FileReadException;
use Sydante\LaravelSensitive\Sensitive;

class UpdateCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensitive:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache sensitive trie tree map';

    /**
     * Execute the console command.
     *
     * @param Sensitive $sensitive
     *
     * @return void
     * @throws CacheException
     * @throws FileReadException
     */
    public function handle(Sensitive $sensitive): void
    {
        if ($sensitive->resetTrieTreeMap()->saveTrieTreeMap()) {
            $this->info('Cache success.');
        } else {
            $this->error('Please configure the cache value to true!');
        }
    }
}
