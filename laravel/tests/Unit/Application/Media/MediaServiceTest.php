<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Media;

use App\Application\Media\Services\MediaService;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\Exceptions\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * MIME allowlist enforcement for uploads.
 */
final class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_svg_mimetype_is_not_allowed(): void
    {
        // SVG was removed from the allowlist to prevent stored XSS.
        $this->assertFalse(MimeType::fromString('image/svg+xml')->isAllowed());

        $this->expectException(ValidationException::class);
        MimeType::fromString('image/svg+xml')->ensureAllowed();
    }

    public function test_upload_rejects_mimetype_not_in_allowlist(): void
    {
        Storage::fake('public');
        $service = app(MediaService::class);

        // A PHP payload smuggled under an image extension must be rejected by
        // ensureAllowed(), even though 'application/x-php' is a well-formed MIME string.
        $this->expectException(ValidationException::class);
        $service->uploadFile(
            filename: 'malicious.png',
            content: '<?php echo 1;',
            mimeTypeString: 'application/x-php',
            sizeBytes: 13,
        );
    }
}
