<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserTest extends TestCase
{
    private User $user;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
        $this->reflection = new ReflectionClass($this->user);
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->user);
    }

    #[Test]
    public function has_fillable_property(): void
    {
        $this->assertTrue($this->reflection->hasProperty('fillable'));
        
        $fillableProperty = $this->reflection->getProperty('fillable');
        $this->assertTrue($fillableProperty->isProtected());
    }

    #[Test]
    public function has_casts_property(): void
    {
        $this->assertTrue($this->reflection->hasProperty('casts'));
        
        $castsProperty = $this->reflection->getProperty('casts');
        $this->assertTrue($castsProperty->isProtected());
    }

    #[Test]
    public function has_hidden_property(): void
    {
        $this->assertTrue($this->reflection->hasProperty('hidden'));
        
        $hiddenProperty = $this->reflection->getProperty('hidden');
        $this->assertTrue($hiddenProperty->isProtected());
    }

    #[Test]
    public function has_get_casts_method(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getCasts'));
        
        $getCastsMethod = $this->reflection->getMethod('getCasts');
        $this->assertTrue($getCastsMethod->isPublic());
        $this->assertEquals('array', $getCastsMethod->getReturnType()?->getName());
    }

    #[Test]
    public function class_structure_is_valid(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
        $this->assertFalse($this->reflection->isInterface());
        $this->assertTrue($this->reflection->isInstantiable());
    }

    #[Test]
    public function has_expected_namespace(): void
    {
        $this->assertEquals('App\Models\User', $this->reflection->getName());
    }
}
