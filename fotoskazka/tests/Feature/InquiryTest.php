<?php

namespace Tests\Feature;

use App\Actions\Inquiry\CreateProjectFromInquiry;
use App\Mail\NewInquiryMail;
use App\Models\Inquiry;
use App\Models\NotificationSetting;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NotificationSetting::create([
            'title' => 'Test',
            'email_enabled' => false,
            'email_recipients' => [],
            'telegram_enabled' => false,
        ]);
    }

    public function test_create_project_from_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create();

        $action = app(CreateProjectFromInquiry::class);
        $project = $action->execute($inquiry, [
            'title' => 'Test Project',
            'type' => 'individual',
            'manager_id' => null,
        ]);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('Test Project', $project->title);
        $this->assertEquals('individual', $project->type);
        $this->assertEquals('draft', $project->status);
        $this->assertEquals($inquiry->id, $project->inquiry->id);
        $this->assertEquals($inquiry->id, Inquiry::find($inquiry->id)->project_id);
    }

    public function test_project_gets_contact_data_from_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create([
            'name' => 'John Doe',
            'phone' => '+7-123-456-78-90',
            'email' => 'john@example.com',
        ]);

        $action = app(CreateProjectFromInquiry::class);
        $project = $action->execute($inquiry, [
            'title' => 'Test Project',
            'type' => 'individual',
        ]);

        $this->assertEquals('John Doe', $project->contact_name);
        $this->assertEquals('+7-123-456-78-90', $project->contact_phone);
        $this->assertEquals('john@example.com', $project->contact_email);
    }

    public function test_create_project_in_transaction(): void
    {
        $inquiry = Inquiry::factory()->create();

        $action = app(CreateProjectFromInquiry::class);
        $project = $action->execute($inquiry, [
            'title' => 'Transactional Project',
            'type' => 'wedding',
            'shooting_date' => '2026-09-15',
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Transactional Project',
            'shooting_date' => '2026-09-15',
        ]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_email_notification_sent_on_inquiry_creation(): void
    {
        Mail::fake();

        NotificationSetting::query()->update([
            'email_enabled' => true,
            'email_recipients' => ['admin@example.com', 'manager@example.com'],
        ]);

        Inquiry::factory()->create();

        Mail::assertSent(NewInquiryMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
        Mail::assertSent(NewInquiryMail::class, function ($mail) {
            return $mail->hasTo('manager@example.com');
        });
    }

    public function test_telegram_notification_sent_on_inquiry_creation(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        NotificationSetting::query()->update([
            'telegram_enabled' => true,
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => 'test-chat',
        ]);

        Inquiry::factory()->create();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org');
        });
    }

    public function test_telegram_error_does_not_break_inquiry_creation(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response([], 500)]);

        NotificationSetting::query()->update([
            'telegram_enabled' => true,
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => 'test-chat',
        ]);

        $inquiry = Inquiry::factory()->create();

        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id]);
    }

    public function test_no_email_when_disabled(): void
    {
        Mail::fake();

        NotificationSetting::query()->update([
            'email_enabled' => false,
            'email_recipients' => ['admin@example.com'],
        ]);

        Inquiry::factory()->create();

        Mail::assertNothingSent();
    }

    public function test_no_telegram_when_disabled(): void
    {
        Http::fake();

        NotificationSetting::query()->update([
            'telegram_enabled' => false,
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => 'test-chat',
        ]);

        Inquiry::factory()->create();

        Http::assertNothingSent();
    }

    public function test_inquiry_belongs_to_project(): void
    {
        $inquiry = Inquiry::factory()->create();
        $project = Project::factory()->create();

        $inquiry->project()->associate($project);
        $inquiry->save();

        $this->assertInstanceOf(Project::class, $inquiry->fresh()->project);
        $this->assertEquals($project->id, $inquiry->fresh()->project_id);
    }

    public function test_project_has_one_inquiry(): void
    {
        $project = Project::factory()->create();
        $inquiry = Inquiry::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Inquiry::class, $project->inquiry);
        $this->assertEquals($inquiry->id, $project->inquiry->id);
    }
}
