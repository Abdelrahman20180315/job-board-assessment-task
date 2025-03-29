<?php

namespace Tests\Unit;

use App\Models\Job;
use App\Models\Language;
use App\Models\Location;
use App\Services\JobFilterService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobFilterTest extends TestCase
{
    // use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_filter_parsing()
    {
        // // Seed languages
        // Language::create(['id' => 1, 'name' => 'PHP']);
        // Language::create(['id' => 2, 'name' => 'JavaScript']);

        // // Seed locations
        // Location::create(['id' => 1, 'city' => 'New York', 'state' => 'NY', 'country' => 'USA']);
        // Location::create(['id' => 2, 'city' => 'Remote', 'state' => null, 'country' => null]);

        // // Seed a job for the first filter
        // $job1 = Job::create([
        //     'title' => 'Software Engineer',
        //     'description' => 'A software engineering job',
        //     'company_name' => 'Tech Corp',
        //     'salary_min' => 60000,
        //     'salary_max' => 80000,
        //     'is_remote' => true,
        //     'job_type' => 'full-time',
        //     'status' => 'published',
        //     'published_at' => now(),
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // Test the first filter
        $query = Job::query();
        $filterService = new JobFilterService($query, 'job_type=full-time AND salary_min>=50000');
        $result = $filterService->apply()->get();
        $this->assertNotEmpty($result);
        // $this->assertEquals($job1->id, $result->first()->id);



        // Test the second filter
        $query = Job::query();
        $filterService = new JobFilterService($query, 'languages HAS_ANY (PHP,JavaScript) AND locations IS_ANY (New York,Remote)');
        $result = $filterService->apply()->get();
        $this->assertNotEmpty($result);
        // $this->assertEquals($job2->id, $result->first()->id);
    }
}
