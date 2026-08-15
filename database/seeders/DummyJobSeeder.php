<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyJobSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = DB::table('job_categories')
            ->where('slug', 'software-development')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('job_categories')->value('id');
        }

        if (! $categoryId) {
            $categoryId = DB::table('job_categories')->insertGetId([
                'name' => 'Software Development',
                'slug' => 'software-development',
                'query_name' => 'software developer',
                'page' => 1,
                'num_page' => 20,
                'timeframe' => 'week',
                'category_image' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('job_listings')->updateOrInsert(
            ['slug' => 'dummy-senior-laravel-developer-lahore'],
            [
                'job_id' => 'DUMMY-LARAVEL-001',
                'uuid' => '7f347e7d-0b25-4eef-93ca-dummy0000001',
                'employer_name' => 'TechNova Solutions',
                'employer_logo' => null,
                'employer_website' => 'https://example.com',
                'employer_company_type' => 'Software Company',
                'publisher' => 'Geezap Demo',
                'employment_type' => 'Full-time',
                'job_title' => 'Senior Laravel Developer',
                'job_category' => $categoryId,
                'apply_link' => 'https://example.com/jobs/senior-laravel-developer',
                'description' => 'This is a dummy job listing created for local testing. TechNova Solutions is looking for a Senior Laravel Developer to build scalable web applications, REST APIs, database-driven systems, queues, caching, and third-party integrations. The ideal candidate has strong PHP and Laravel experience, understands clean architecture and testing, and can collaborate with frontend and product teams.',
                'is_remote' => false,
                'city' => 'Lahore',
                'state' => 'Punjab',
                'country' => 'PK',
                'latitude' => '31.5204',
                'longitude' => '74.3587',
                'google_link' => null,
                'posted_at' => now()->toDateTimeString(),
                'expired_at' => now()->addDays(30)->toDateTimeString(),
                'min_salary' => 180000,
                'max_salary' => 300000,
                'salary_currency' => 'PKR',
                'salary_period' => 'month',
                'benefits' => json_encode([
                    'Health insurance',
                    'Performance bonus',
                    'Flexible working hours',
                ]),
                'qualifications' => json_encode([
                    '3+ years of PHP/Laravel experience',
                    'Strong MySQL and REST API knowledge',
                    'Experience with Git and modern development workflows',
                ]),
                'responsibilities' => json_encode([
                    'Develop and maintain Laravel applications',
                    'Design and optimize REST APIs and database queries',
                    'Integrate third-party services and background jobs',
                    'Write maintainable and testable code',
                ]),
                'required_experience' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Homepage/job-list caches can otherwise hide the newly seeded record for hours.
        Cache::flush();

        $this->command?->info('Dummy job created: Senior Laravel Developer at TechNova Solutions (Lahore).');
    }
}
