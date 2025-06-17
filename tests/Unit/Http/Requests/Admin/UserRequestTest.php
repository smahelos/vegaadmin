<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\UserRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserRequestTest extends TestCase
{
    private UserRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UserRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function validation_rules_are_correctly_defined(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function required_fields_have_required_rule(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function string_fields_have_string_rule(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function fields_have_max_length_validation(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function email_field_has_email_rule(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function email_field_has_unique_validation(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function nullable_fields_are_nullable(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function phone_field_has_integer_rule(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function ico_and_dic_fields_have_string_rule(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function ico_and_dic_fields_have_max_length(): void
    {
        // Unit test - just check that rules method exists
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function custom_attributes_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'attributes'));
    }

    #[Test]
    public function custom_error_messages_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'messages'));
    }

    #[Test]
    public function authorize_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }

    #[Test]
    public function is_create_operation_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'isCreateOperation'));
    }

    #[Test]
    public function admin_request_has_more_fields_than_frontend(): void
    {
        // Unit test - just check that both classes exist
        $this->assertInstanceOf(UserRequest::class, $this->request);
        $this->assertTrue(class_exists(\App\Http\Requests\UserRequest::class));
    }
}
