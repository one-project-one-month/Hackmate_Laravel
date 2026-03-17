<?php

namespace App\Console\Commands;

use App\Models\Feed;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateProjectRecommendations extends Command
{
    protected $signature = 'app:generate-project-recommendations';

    protected $description = 'Generate project recommendation feed from hidden reaction counters';

    public function handle(): int
    {
        $generatedAt = Carbon::now();
        $batchSize = 1000;
        $insertBuffer = [];
        $rankCounter = 1;
        $totalCount = 0;

        $this->info('Starting feed generation...');

        DB::transaction(function () use ($generatedAt, $batchSize, &$insertBuffer, &$rankCounter, &$totalCount) {
            Feed::truncate();

            $query = Project::query()
                ->where('is_active', true)
                ->orderByRaw('(like_count - dislike_count) DESC')
                ->orderByDesc('like_count')
                ->orderByDesc('created_at')
                ->get(['id', 'like_count', 'dislike_count']);

            foreach ($query as $project) {
                $insertBuffer[] = [
                    'project_id' => $project->id,
                    'score' => $project->like_count - $project->dislike_count,
                    'rank' => $rankCounter++,
                    'generated_at' => $generatedAt,
                    'created_at' => $generatedAt,
                    'updated_at' => $generatedAt,
                ];

                if (count($insertBuffer) >= $batchSize) {
                    Feed::insert($insertBuffer);
                    $totalCount += count($insertBuffer);
                    $insertBuffer = [];
                    $this->comment("Processed {$totalCount} items...");
                }
            }

            if (! empty($insertBuffer)) {
                Feed::insert($insertBuffer);
                $totalCount += count($insertBuffer);
            }
        });

        $this->info("Success! Generated {$totalCount} feed items at {$generatedAt->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
