<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\Language;
use App\Models\Location;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\JobAttributeValue;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seed Languages
        $languages = ['PHP', 'JavaScript', 'Python', 'Java', 'Ruby'];
        foreach ($languages as $language) {
            Language::create(['name' => $language]);
        }

        // Seed Locations
        $locations = [
            ['city' => 'New York', 'state' => 'NY', 'country' => 'USA'],
            ['city' => 'San Francisco', 'state' => 'CA', 'country' => 'USA'],
            ['city' => 'Remote', 'state' => null, 'country' => 'Global'],
        ];
        foreach ($locations as $location) {
            Location::create($location);
        }

        // Seed Categories
        $categories = ['Engineering', 'Design', 'Marketing', 'Sales'];
        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }

        // Seed Attributes
        $attributes = [
            ['name' => 'years_experience', 'type' => 'number', 'options' => null],
            ['name' => 'requires_degree', 'type' => 'boolean', 'options' => null],
            ['name' => 'level', 'type' => 'select', 'options' => json_encode(['Junior', 'Mid', 'Senior'])],
        ];
        foreach ($attributes as $attribute) {
            Attribute::create($attribute);
        }

        // Seed Jobs
        for ($i = 0; $i < 50; $i++) {
            $job = Job::create([
                'title' => "Job Title $i",
                'description' => "Description for job $i",
                'company_name' => "Company $i",
                'salary_min' => rand(40000, 80000),
                'salary_max' => rand(80000, 120000),
                'is_remote' => rand(0, 1),
                'job_type' => ['full-time', 'part-time', 'contract', 'freelance'][rand(0, 3)],
                'status' => ['draft', 'published', 'archived'][rand(0, 2)],
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            // Attach relationships
            $job->languages()->attach(Language::inRandomOrder()->take(rand(1, 3))->pluck('id'));
            $job->locations()->attach(Location::inRandomOrder()->take(rand(1, 2))->pluck('id'));
            $job->categories()->attach(Category::inRandomOrder()->take(rand(1, 2))->pluck('id'));

            // Add EAV attributes
            JobAttributeValue::create([
                'job_id' => $job->id,
                'attribute_id' => Attribute::where('name', 'years_experience')->first()->id,
                'value' => rand(1, 10),
            ]);
            JobAttributeValue::create([
                'job_id' => $job->id,
                'attribute_id' => Attribute::where('name', 'requires_degree')->first()->id,
                'value' => rand(0, 1) ? 'true' : 'false',
            ]);
            JobAttributeValue::create([
                'job_id' => $job->id,
                'attribute_id' => Attribute::where('name', 'level')->first()->id,
                'value' => ['Junior', 'Mid', 'Senior'][rand(0, 2)],
            ]);
        }
    }
}
