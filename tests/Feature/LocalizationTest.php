<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_is_default(): void
    {
        $this->get('/files')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Uploaded files');
    }

    public function test_language_can_be_switched_to_russian_and_back(): void
    {
        $this->from('/files')->get('/locale/ru')->assertRedirect('/files');
        $this->get('/files')->assertSee('<html lang="ru">', false)->assertSee('Загруженные файлы');

        $this->get('/locale/en');
        $this->get('/files')->assertSee('Uploaded files');
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->get('/locale/de')->assertNotFound();
    }

    public function test_validation_messages_follow_selected_language(): void
    {
        $this->postJson('/upload')->assertJsonValidationErrors(['file' => 'Choose a file to upload.']);

        $this->get('/locale/ru');
        $this->postJson('/upload')->assertJsonValidationErrors(['file' => 'Выберите файл для загрузки.']);
    }

    public function test_notification_language_does_not_depend_on_visitor_language(): void
    {
        Storage::fake('local');
        config(['notifications.locale' => 'en']);

        $this->get('/locale/ru');
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertCreated();

        $this->deleteJson('/files/'.StoredFile::sole()->id)
            ->assertOk()
            ->assertJsonPath('message', 'Файл «doc.pdf» удалён.');

        $payload = $this->publisher->published[0]['payload'];
        $this->assertSame('File deleted: doc.pdf', $payload['subject']);
        $this->assertStringContainsString('was deleted manually', $payload['body']);
    }
}
