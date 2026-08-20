<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobListing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RepairNjpDescriptions extends Command
{
    protected $signature = 'jobs:repair-njp-descriptions {--dry-run : Show how many rows need repair without writing changes}';

    protected $description = 'Permanently remove NJP page navigation/footer/JavaScript artifacts from imported job descriptions.';

    public function handle(): int
    {
        config()->set('scout.driver', 'database');

        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $repaired = 0;

        $jobs = JobListing::withoutGlobalScopes()
            ->where(function ($query) {
                $query->where('publisher', 'like', '%National Jobs Portal%')
                    ->orWhere('description', 'like', '%isDescriptionExpanded%')
                    ->orWhere('description', 'like', '%Toggle Job Description Read More / Less%')
                    ->orWhere('description', 'like', '%toggle-description-btn%')
                    ->orWhere('description', 'like', '%info@njp.gov.pk%');
            })
            ->orderBy('id')
            ->cursor();

        foreach ($jobs as $job) {
            $checked++;
            $raw = (string) ($job->getRawOriginal('description') ?? '');

            // Re-assigning runs JobListing's description mutator with the latest
            // robust NJP cleaner. Read the raw attribute after mutation so the
            // permanent DB write contains the cleaned value, not only accessor output.
            $job->description = $raw;
            $clean = (string) ($job->getAttributes()['description'] ?? '');

            if ($clean === $raw) {
                continue;
            }

            $repaired++;

            if (! $dryRun) {
                DB::table('job_listings')
                    ->where('id', $job->id)
                    ->update([
                        'description' => $clean,
                        'updated_at' => now(),
                    ]);
            }
        }

        if (! $dryRun) {
            // Existing detail/list caches may contain serialized pre-repair rows.
            // Clearing application cache ensures repaired descriptions are visible now.
            Cache::flush();
        }

        $this->info(sprintf(
            '%s Checked %d NJP jobs; %d description%s %s.',
            $dryRun ? '[DRY RUN]' : 'Done.',
            $checked,
            $repaired,
            $repaired === 1 ? '' : 's',
            $dryRun ? 'need repair' : 'repaired'
        ));

        return self::SUCCESS;
    }
}
