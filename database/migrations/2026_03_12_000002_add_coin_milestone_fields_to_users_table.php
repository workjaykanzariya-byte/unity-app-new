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
            $table->string('coin_medal_rank', 100)->nullable();
            $table->string('coin_milestone_title', 150)->nullable();
            $table->text('coin_milestone_meaning')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE users
            SET
                coin_medal_rank = CASE
                    WHEN coins_balance >= 10000000 THEN 'Crown'
                    WHEN coins_balance >= 7500000 THEN 'Champion'
                    WHEN coins_balance >= 5000000 THEN 'Super Star'
                    WHEN coins_balance >= 3000000 THEN 'Royal'
                    WHEN coins_balance >= 2000000 THEN 'Supreme'
                    WHEN coins_balance >= 1500000 THEN 'Elite'
                    WHEN coins_balance >= 1000000 THEN 'Diamond'
                    WHEN coins_balance >= 750000 THEN 'Titanium'
                    WHEN coins_balance >= 500000 THEN 'Platinum'
                    WHEN coins_balance >= 300000 THEN 'Gold'
                    WHEN coins_balance >= 200000 THEN 'Silver'
                    WHEN coins_balance >= 100000 THEN 'Bronze'
                    ELSE NULL
                END,
                coin_milestone_title = CASE
                    WHEN coins_balance >= 10000000 THEN 'Peers Global Titan 👑'
                    WHEN coins_balance >= 7500000 THEN 'Ecosystem Architect'
                    WHEN coins_balance >= 5000000 THEN 'Global Collaborator'
                    WHEN coins_balance >= 3000000 THEN 'Unity Champion'
                    WHEN coins_balance >= 2000000 THEN 'Collaboration Champion'
                    WHEN coins_balance >= 1500000 THEN 'Collaboration Legend'
                    WHEN coins_balance >= 1000000 THEN 'Community Star'
                    WHEN coins_balance >= 750000 THEN 'Synergy Architect'
                    WHEN coins_balance >= 500000 THEN 'Growth Champion'
                    WHEN coins_balance >= 300000 THEN 'Action Leader'
                    WHEN coins_balance >= 200000 THEN 'Network Builder'
                    WHEN coins_balance >= 100000 THEN 'Unity Builder'
                    ELSE NULL
                END,
                coin_milestone_meaning = CASE
                    WHEN coins_balance >= 10000000 THEN 'The summit of contribution. A beacon of excellence and an icon of Peers Global legacy.'
                    WHEN coins_balance >= 7500000 THEN 'Wherever you show up, growth follows — you multiply possibilities for others.'
                    WHEN coins_balance >= 5000000 THEN 'Your influence now spans regions — collaboration at scale, value at every level.'
                    WHEN coins_balance >= 3000000 THEN 'You’re not just a member; you’re an engine of progress across multiple circles.'
                    WHEN coins_balance >= 2000000 THEN 'You’ve cracked the code — contribution with consistency and strategic purpose.'
                    WHEN coins_balance >= 1500000 THEN 'Trusted by all, respected by many — your presence drives people to take action.'
                    WHEN coins_balance >= 1000000 THEN 'Your actions inspire. You''re not just growing — you''re building community strength.'
                    WHEN coins_balance >= 750000 THEN 'You know how to collaborate deeply, create win-wins, and keep the vibe high.'
                    WHEN coins_balance >= 500000 THEN 'You''re making your mark with energy, referrals, visibility, and engagement.'
                    WHEN coins_balance >= 300000 THEN 'You lift your peers up, contribute actively, and power up your circle''s performance.'
                    WHEN coins_balance >= 200000 THEN 'Reliable, trusted, and visibly present. You’re building real, lasting momentum.'
                    WHEN coins_balance >= 100000 THEN 'You’ve stepped into the game — your journey of consistent contribution has begun.'
                    ELSE NULL
                END
        SQL);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'coin_medal_rank',
                'coin_milestone_title',
                'coin_milestone_meaning',
            ]);
        });
    }
};
