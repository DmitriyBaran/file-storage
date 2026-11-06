<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_pages_are_available(): void
    {
        $this->get('/upload')->assertOk()->assertSee('Upload files');
        $this->get('/files')->assertOk()->assertSee('Uploaded files');
    }

    public static function validFiles(): array
    {
        return [
            'pdf' => ['report.pdf', 'application/pdf'],
            'docx' => ['letter.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
    }

    #[DataProvider('validFiles')]
    public function test_valid_file_is_stored_with_24h_ttl(string $name, string $mime): void
    {
        $this->freezeSecond();

        $response = $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create($name, 500, $mime),
        ]);

        $response->assertCreated()->assertJsonPath('file.original_name', $name);

        $file = StoredFile::sole();
        Storage::disk('local')->assertExists($file->path);
        $this->assertTrue($file->expires_at->equalTo(now()->addHours(24)));
    }

    public function test_file_larger_than_10mb_is_rejected(): void
    {
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_wrong_type_is_rejected(): void
    {
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create('image.png', 10, 'image/png'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_renamed_file_is_rejected_by_content(): void
    {
        // Расширение .pdf, но по содержимому — картинка
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create('fake.pdf', 10, 'image/png'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_file_name_longer_than_column_is_rejected(): void
    {
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create(str_repeat('я', 252).'.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['file' => '255']);

        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_file_name_of_max_length_is_accepted(): void
    {
        $name = str_repeat('я', 251).'.pdf';

        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create($name, 10, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('file.original_name', $name);
    }

    public function test_file_can_be_downloaded_before_expiration(): void
    {
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertCreated();

        $this->get('/files/'.StoredFile::sole()->id.'/download')
            ->assertOk()
            ->assertDownload('report.pdf');
    }

    public function test_expired_file_cannot_be_downloaded_even_if_not_purged_yet(): void
    {
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertCreated();

        $this->travel(24)->hours();

        $this->get('/files/'.StoredFile::sole()->id.'/download')->assertStatus(410);
    }

    public function test_file_is_required(): void
    {
        $this->postJson('/upload')->assertUnprocessable()->assertJsonValidationErrors('file');
    }
}
