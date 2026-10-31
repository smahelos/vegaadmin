<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserActivitySummary;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserActivitySummaryFeatureTest extends TestCase
{
    #[Test]
    public function the_summary_counts_businesses_and_contacts_including_the_archive_and_one_off_invoices(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);
        Business::factory()->archived()->create(['user_id' => $user->id]);
        Contact::factory()->create(['user_id' => $user->id]);
        Contact::factory()->archived()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'business_id' => $business->id]);
        Contact::factory()->create();
        Business::factory()->create();
        $summary = UserActivitySummary::where('user_id', $user->id)->sole();

        $this->assertSame($user->id, $summary->user->id);
        $this->assertSame(2, $summary->total_contacts);
        $this->assertSame(2, $summary->total_businesses);
        $this->assertSame(1, $summary->total_invoices);
    }
}
