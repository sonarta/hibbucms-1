<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled posts that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $posts = Post::where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts to publish.');
            return 0;
        }

        $count = 0;
        foreach ($posts as $post) {
            $post->update(['status' => 'published']);
            $this->line("Published: {$post->title}");
            $count++;
        }

        $this->info("Published {$count} scheduled post(s).");
        return 0;
    }
}
