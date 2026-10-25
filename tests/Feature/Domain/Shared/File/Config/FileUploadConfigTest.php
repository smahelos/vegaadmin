<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Shared\File\Config;

use App\Domain\Shared\File\Config\FileUploadConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FileUploadConfigTest extends TestCase
{
    #[Test]
    public function default_context_returns_configured_defaults(): void
    {
        $context = app(FileUploadConfig::class)->forContext();

        $this->assertGreaterThan(0, $context->getMaxKb());
        $this->assertContains('jpg', $context->getAllowedExtensions());
        $this->assertTrue($context->isThumbnailEnabled());
    }

    #[Test]
    public function specific_context_overrides_defaults(): void
    {
        $context = app(FileUploadConfig::class)->forContext('invoice_logo');

        $this->assertSame(2048, $context->getMaxKb());
        $this->assertContains('png', $context->getAllowedExtensions());
        $this->assertTrue($context->isThumbnailEnabled());
        $this->assertSame(300, $context->getThumbnailWidth());
    }

    #[Test]
    public function unknown_context_falls_back_to_default(): void
    {
        $config = app(FileUploadConfig::class);

        $this->assertSame($config->forContext()->getMaxKb(), $config->forContext('non_existing_x')->getMaxKb());
    }

    #[Test]
    public function hash_deduplication_is_enabled_per_context(): void
    {
        $config = app(FileUploadConfig::class);

        $this->assertTrue($config->forContext('invoice_logo')->isHashDeduplicationEnabled());
        $this->assertFalse($config->forContext()->isHashDeduplicationEnabled());
    }
}
