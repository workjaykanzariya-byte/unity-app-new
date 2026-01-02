<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_member_resource_returns_extended_profile_fields(): void
    {
        $user = new User([
            'id' => '1f6a2c40-57b0-4b7b-8c5d-879f9d8f2ea7',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'display_name' => 'Jane Doe',
            'company_name' => 'Acme Inc',
            'designation' => 'Product Lead',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'membership_status' => 'premium',
            'membership_expiry' => Carbon::parse('2025-01-01T00:00:00Z'),
            'coins_balance' => 150,
            'business_type' => 'b2b',
            'turnover_range' => '1-5Cr',
            'gender' => 'female',
            'dob' => Carbon::parse('1990-05-12'),
            'experience_years' => 10,
            'experience_summary' => 'Leading product teams',
            'short_bio' => 'Short bio',
            'long_bio_html' => '<p>Long bio</p>',
            'industry_tags' => ['it-services'],
            'skills' => ['sales'],
            'interests' => ['travel'],
            'target_regions' => ['IN'],
            'target_business_categories' => ['SaaS'],
            'hobbies_interests' => ['reading'],
            'leadership_roles' => ['founder'],
            'special_recognitions' => ['award'],
            'social_links' => ['linkedin' => 'https://linkedin.com/in/jane', 'facebook' => null],
            'profile_photo_file_id' => 'profile-file',
            'cover_photo_file_id' => 'cover-file',
            'address' => '123 Street',
            'state' => 'KA',
            'country' => 'India',
            'pincode' => '560001',
            'is_verified' => true,
            'is_sponsored_member' => false,
            'last_login_at' => Carbon::parse('2024-01-01T10:00:00Z'),
            'created_at' => Carbon::parse('2024-01-02T10:00:00Z'),
            'updated_at' => Carbon::parse('2024-02-02T10:00:00Z'),
        ]);

        $user->twitter = 'https://twitter.com/jane';

        $resource = (new UserResource($user))->toArray(request());

        $this->assertSame('Product Lead', $resource['designation']);
        $this->assertSame(['sales'], $resource['skills']);
        $this->assertSame('1990-05-12', $resource['dob']);
        $this->assertSame(url('/api/v1/files/cover-file'), $resource['cover_photo_url']);
        $this->assertSame('https://twitter.com/jane', $resource['social_links']['twitter']);
        $this->assertNull($resource['social_links']['youtube']);
        $this->assertSame('Short bio', $resource['bio']);
    }
}
