<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['notifications.email.to' => 'ops@example.com']);
    }

    private function upload(string $name = 'doc.pdf'): StoredFile
    {
        $this->postJson('/upload', [
            'file' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
        ])->assertCreated();

        return StoredFile::latest('id')->first();
    }

    public function test_manual_delete_removes_file_and_publishes_email_notification(): void
    {
        $file = $this->upload();

        $this->deleteJson("/files/{$file->id}")->assertOk();

        $this->assertModelMissing($file);
        Storage::disk('local')->assertMissing($file->path);

        $this->assertCount(1, $this->publisher->published);
        $message = $this->publisher->published[0];
        $this->assertSame('notifications.email', $message['queue']);
        $this->assertSame('ops@example.com', $message['payload']['to']);
        $this->assertSame('manual', $message['payload']['context']['reason']);
    }

    public function test_expired_files_are_purged_with_notification(): void
    {
        $old = $this->upload('old.pdf');
        $this->travel(23)->hours();
        $fresh = $this->upload('fresh.pdf');
        $this->travel(2)->hours(); // old: 25ч, fresh: 2ч

        $this->artisan('files:purge-expired')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($fresh);
        Storage::disk('local')->assertMissing($old->path);

        $this->assertCount(1, $this->publisher->published);
        $this->assertSame('expired', $this->publisher->published[0]['payload']['context']['reason']);
    }

    public function test_deleting_missing_file_returns_404_and_sends_nothing(): void
    {
        $this->deleteJson('/files/999')->assertNotFound();
        $this->assertSame([], $this->publisher->published);
    }

    public function test_broker_failure_does_not_break_deletion(): void
    {
        $file = $this->upload();

        $this->app->instance(\App\Messaging\Contracts\MessagePublisher::class, new class implements \App\Messaging\Contracts\MessagePublisher {
            public function publish(string $queue, array $payload): void
            {
                throw new \RuntimeException('RabbitMQ is down');
            }
        });
        $this->app->forgetInstance(\App\Notifications\Contracts\Notifier::class);

        $this->deleteJson("/files/{$file->id}")->assertOk();
        $this->assertModelMissing($file);
    }
}
