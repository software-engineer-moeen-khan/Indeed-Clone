<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobListing;
use App\Support\JobDescriptionSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CleanupImportedJobDescriptions extends Command
{
    protected $signature = 'jobs:cleanup-imported-descriptions
                            {--dry-run : Show how many NJP descriptions need cleanup without changing data}';

    protected $description = 'Remove scraped NJP CSS, navigation text and excessive whitespace from imported job descriptions.';

    public function handle(): int
    {
        config()->set('scout.driver', 'database');

        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $changed = 0;
        $unchanged = 0;

        $query = JobListing::withoutGlobalScopes()
            ->where(function ($query): void {
                $query->where('publisher', 'like', '%National Jobs Portal%')
                    ->orWhere('publisher', 'like', '%National Job Portal%')
                    ->orWhere('apply_link', 'like', '%njp.gov.pk%');
            })
            ->select(['id', 'job_title', 'employer_name', 'description']);

        $total = (clone $query)->count();
        $this->info("Checking {$total} imported NJP job description(s)...");

        $query->orderBy('id')->chunkById(100, function ($jobs) use ($dryRun, &$checked, &$changed, &$unchanged): void {
            foreach ($jobs as $job) {
                $checked++;
                $before = trim((string) $job->description);
                $after = JobDescriptionSanitizer::sanitize(
                    $before,
                    (string) $job->job_title,
                    (string) $job->employer_name,
                );

                if ($after === $before) {
                    $unchanged++;
                    continue;
                }

                $changed++;

                if (! $dryRun) {
                    // Direct DB update avoids one Scout/Typesense indexing request per job.
                    DB::table('job_listings')->where('id', $job->id)->update([
                        'description' => $after,
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        if (! $dryRun && $changed > 0) {
            foreach (['jobCategories', 'jobCategoriesJobsCount', 'jobCategoriesAll'] as $cacheKey) {
                Cache::forget($cacheKey);
            }
        }

        $this->newLine();
        $this->table(
            ['Checked', $dryRun ? 'Would clean' : 'Cleaned', 'Already clean'],
            [[$checked, $changed, $unchanged]]
        );

        if ($dryRun) {
            $this->comment('Dry run only. No database rows were changed.');
        } else {
            $this->info('Imported NJP descriptions are now compact and RichEditor-style.');
            $this->line('Run php artisan optimize:clear after this command so every public job page shows the cleaned text immediately.');
        }

        return self::SUCCESS;
    }
}
