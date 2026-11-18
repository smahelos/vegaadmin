<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Forms\Party;

use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PartyFormFieldsTest extends TestCase
{
    #[Test]
    public function contact_names_tell_contacts_of_the_same_name_apart_by_owner(): void
    {
        $userFactory = User::factory();
        $first = $userFactory->create(['name' => 'Owner ' . uniqid()]);
        $second = $userFactory->create(['name' => 'Owner ' . uniqid()]);
        $name = 'Contact ' . uniqid();
        $contactFactory = Contact::factory();
        $firstContact = $contactFactory->create(
            [
                'user_id' => $first->id,
                'name' => $name,
            ],
        );
        $secondContact = $contactFactory->create(
            [
                'user_id' => $second->id,
                'name' => $name,
            ],
        );

        $names = PartyFormFields::contactNames();

        $this->assertSame($name . ' (' . $first->name . ')', $names[$firstContact->id]);
        $this->assertSame($name . ' (' . $second->name . ')', $names[$secondContact->id]);
    }
}
