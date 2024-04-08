<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Story;

class StoryDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:story-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stories will disappear after 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDateTime = Carbon::now();
        $story = Story::where('expires_at', '<', $currentDateTime)->delete();
        $this->info('Stories deleted successfully after 24 Hours.');
    }
}
