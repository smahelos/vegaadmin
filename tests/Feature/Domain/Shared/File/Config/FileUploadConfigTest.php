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
        $fileUploadConfig = app(FileUploadConfig::class);
        $context = $fileUploadConfig->forContext();

        $this->assertGreaterThan(0, $context->getMaxKb());
        $this->assertContains('jpg', $context->getAllowedExtensions());
        $this->assertTrue($context->isThumbnailEnabled());
    }

    #[Test]
    public function specific_context_overrides_defaults(): void
    {
        $fileUploadConfig = app(FileUploadConfig::class);
        $context = $fileUploadConfig->forContext('invoice_logo');

        $this->assertSame(2048, $context->getMaxKb());
        $this->assertContains('png', $context->getAllowedExtensions());
        $this->assertTrue($context->isThumbnailEnabled());
        $this->assertSame(300, $context->getThumbnailWidth());
    }

    #[Test]
    public function unknown_context_falls_back_to_default(): void
    {
        $config = app(FileUploadConfig::class);

        $defaultContext = $config->forContext();
        $unknownContext = $config->forContext('non_existing_x');
        $this->assertSame(
            $defaultContext->getMaxKb(),
            $unknownContext->getMaxKb(),
        );
    }

    #[Test]
    public function hash_deduplication_is_enabled_per_context(): void
    {
        $config = app(FileUploadConfig::class);

        $invoiceLogoContext = $config->forContext('invoice_logo');
        $this->assertTrue(
            $invoiceLogoContext->isHashDeduplicationEnabled(),
        );
        $defaultContext = $config->forContext();
        $this->assertFalse($defaultContext->isHashDeduplicationEnabled());
    }
}
