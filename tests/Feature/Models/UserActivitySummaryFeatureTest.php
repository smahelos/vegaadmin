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
        $userFactory = User::factory();
        $user = $userFactory->create();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $user->id]);
        Business::factory()->archived()->create(['user_id' => $user->id]);
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $user->id]);
        Contact::factory()->archived()->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['user_id' => $user->id, 'business_id' => $business->id]);
        $contactFactory2 = Contact::factory();
        $contactFactory2->create();
        $businessFactory2 = Business::factory();
        $businessFactory2->create();
        $userActivitySummaryQuery = UserActivitySummary::where('user_id', $user->id);
        $summary = $userActivitySummaryQuery->sole();

        $this->assertSame($user->id, $summary->user->id);
        $this->assertSame(2, $summary->total_contacts);
        $this->assertSame(2, $summary->total_businesses);
        $this->assertSame(1, $summary->total_invoices);
    }
}
