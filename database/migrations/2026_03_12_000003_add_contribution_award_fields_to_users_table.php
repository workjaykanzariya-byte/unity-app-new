<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('contribution_award_name', 150)->nullable();
            $table->text('contribution_award_recognition')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE users
            SET
                contribution_award_name = CASE
                    WHEN members_introduced_count >= 25 THEN 'Peers Global Hall of Fame 👑'
                    WHEN members_introduced_count >= 20 THEN 'Nation Builder Award'
                    WHEN members_introduced_count >= 15 THEN 'Impact Creator Award'
                    WHEN members_introduced_count >= 12 THEN 'Legacy Creator'
                    WHEN members_introduced_count >= 10 THEN 'Global Star'
                    WHEN members_introduced_count >= 8 THEN 'Super Star Award'
                    WHEN members_introduced_count >= 6 THEN 'Inspiration Icon Award'
                    WHEN members_introduced_count >= 5 THEN 'Influencer Award'
                    WHEN members_introduced_count >= 4 THEN 'Voice of Change Award'
                    WHEN members_introduced_count >= 3 THEN 'Community Catalyst Award'
                    WHEN members_introduced_count >= 2 THEN 'Rising Voice Award'
                    WHEN members_introduced_count >= 1 THEN 'The Connector Award'
                    ELSE NULL
                END,
                contribution_award_recognition = CASE
                    WHEN members_introduced_count >= 25 THEN 'Crown Pin + Lifetime Badge + Unity Wall of Fame Feature'
                    WHEN members_introduced_count >= 20 THEN 'Trophy + National Platform Honor'
                    WHEN members_introduced_count >= 15 THEN '₹1L Membership Credit (in kind) + City Convention Recognition'
                    WHEN members_introduced_count >= 12 THEN 'Trophy + Digital Certificate + Social Media Spotlight'
                    WHEN members_introduced_count >= 10 THEN 'Recognition at City Meet'
                    WHEN members_introduced_count >= 8 THEN 'Premium Recognition + Podcast Invite'
                    WHEN members_introduced_count >= 6 THEN 'Digital Certificate + Social Media Spotlight'
                    WHEN members_introduced_count >= 5 THEN 'Entry to Influencers Club'
                    WHEN members_introduced_count >= 4 THEN 'Digital Certificate + Social Media Spotlight'
                    WHEN members_introduced_count >= 3 THEN 'Digital Certificate + Social Media Spotlight'
                    WHEN members_introduced_count >= 2 THEN 'Digital Certificate + Social Media Spotlight'
                    WHEN members_introduced_count >= 1 THEN 'Digital Certificate + Social Media Spotlight'
                    ELSE NULL
                END
        SQL);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'contribution_award_name',
                'contribution_award_recognition',
            ]);
        });
    }
};
