<?php

namespace Tests\Unit\Domain\Shared\File\Config;

use App\Domain\Shared\File\Config\FileUploadConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileUploadConfigTest extends TestCase
{
    private FileUploadConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = app(FileUploadConfig::class);
    }

    #[Test]
    public function default_context_returns_configured_defaults(): void
    {
        $ctx = $this->config->forContext();
        $this->assertGreaterThan(0, $ctx->getMaxKb());
        $this->assertContains('jpg', $ctx->getAllowedExtensions());
        $this->assertTrue($ctx->isThumbnailEnabled());
    }

    #[Test]
    public function specific_context_overrides_defaults(): void
    {
        $ctx = $this->config->forContext('invoice_logo');
        $this->assertSame(2048, $ctx->getMaxKb());
        $this->assertTrue(in_array('png', $ctx->getAllowedExtensions(), true));
        $this->assertTrue($ctx->isThumbnailEnabled());
        $this->assertSame(300, $ctx->getThumbnailWidth());
    }

    #[Test]
    public function unknown_context_falls_back_to_default(): void
    {
        $ctxUnknown = $this->config->forContext('non_existing_x');
        $ctxDefault = $this->config->forContext();
        $this->assertSame($ctxDefault->getMaxKb(), $ctxUnknown->getMaxKb());
    }

    #[Test]
    public function hash_deduplication_flag_is_exposed_for_context(): void
    {
        $ctx = $this->config->forContext('invoice_logo');
        $this->assertTrue($ctx->isHashDeduplicationEnabled());
        $default = $this->config->forContext();
        $this->assertFalse($default->isHashDeduplicationEnabled());
    }
}
