<?php

namespace Tests\Feature;

use App\Jobs\CreateBackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use App\Services\BackupProgressManager;
use App\Services\BackupService;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'contact_number' => '0123456789',
        ]);
    }

    public function test_progress_manager_tracks_state()
    {
        $manager = new BackupProgressManager();
        $jobId = 'test_' . uniqid();

        $state = $manager->init($jobId, 'backup', 'Testing Backup');
        $this->assertEquals('queued', $state['status']);
        $this->assertEquals($jobId, $state['job_id']);

        $manager->update($jobId, [
            'status' => 'in_progress',
            'stage' => 'Dumping tables',
            'percentage' => 20,
        ]);

        $manager->log($jobId, 'First log message');

        $retrieved = $manager->get($jobId);
        $this->assertEquals('in_progress', $retrieved['status']);
        $this->assertEquals(20, $retrieved['percentage']);
        $this->assertCount(2, $retrieved['logs']);

        $manager->complete($jobId, ['filename' => 'backup.zip']);
        $completed = $manager->get($jobId);
        $this->assertEquals('completed', $completed['status']);
        $this->assertEquals(100, $completed['percentage']);
    }

    public function test_super_admin_can_access_backup_page()
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get(route('backup.index'));
        $response->assertStatus(200);
        $response->assertSee('Backup Now');
        $response->assertSee('RESTORE');
        $response->assertSee('cPanel Cron Setup');
    }

    public function test_backup_now_dispatches_queued_job()
    {
        Queue::fake();
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->postJson(route('backup.run'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $jobId = $response->json('job_id');
        $this->assertNotEmpty($jobId);

        Queue::assertPushed(CreateBackupJob::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });

        // Test polling endpoint
        $statusResponse = $this->actingAs($admin)->getJson(route('backup.status', ['jobId' => $jobId]));
        $statusResponse->assertStatus(200);
        $this->assertEquals('queued', $statusResponse->json('status'));
    }

    public function test_restore_requires_confirmation_phrase()
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->postJson(route('backup.restore.run'), [
            'confirm_phrase' => 'WRONG',
            'existing_backup' => 'fake.zip',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_full_backup_job_creates_valid_zip()
    {
        // Setup mock storage and test image
        Storage::fake('public');
        Storage::disk('public')->put('bills/test_receipt.jpg', 'fake-image-content');

        $company = Company::create([
            'name' => 'Test Company',
            'bill_id_prefix' => 'TC',
        ]);

        Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'TC-001',
            'amount' => 150.00,
            'date' => now(),
            'media_attachment' => 'bills/test_receipt.jpg',
        ]);

        $jobId = 'test_backup_job_' . uniqid();
        $progress = new BackupProgressManager();
        $dbService = new DatabaseBackupService();
        $backupService = new BackupService();

        $progress->init($jobId, 'backup', 'Test Full Backup');

        $job = new CreateBackupJob($jobId);
        $job->handle($progress, $dbService, $backupService);

        $state = $progress->get($jobId);
        $this->assertEquals('completed', $state['status']);
        $this->assertNotNull($state['result']);

        $filename = $state['result']['filename'];
        $zipPath = storage_path('app/backups/' . $filename);
        $this->assertFileExists($zipPath);

        // Inspect ZIP structure
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);

        // Check for database.sql.gz
        $hasDb = false;
        $hasImage = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === 'database.sql.gz') {
                $hasDb = true;
            }
            if (str_starts_with($name, 'images/')) {
                $hasImage = true;
            }
        }

        $this->assertTrue($hasDb, 'ZIP must contain database.sql.gz at root');
        $this->assertTrue($hasImage, 'ZIP must contain images/ directory');
        $zip->close();

        // Cleanup
        @unlink($zipPath);
    }

    public function test_restore_job_restores_database_and_images()
    {
        Storage::fake('public');
        Storage::disk('public')->put('bills/original_image.jpg', 'original-content');

        $company = Company::create([
            'name' => 'Original Company',
            'bill_id_prefix' => 'ORIG',
        ]);

        Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'ORIG-001',
            'amount' => 500.00,
            'date' => now(),
            'media_attachment' => 'bills/original_image.jpg',
        ]);

        $progress = new BackupProgressManager();
        $dbService = new DatabaseBackupService();
        $backupService = new BackupService();

        // 1. Create backup
        $backupJobId = 'backup_test_' . uniqid();
        $progress->init($backupJobId, 'backup', 'Test Backup for Restore');
        $backupJob = new CreateBackupJob($backupJobId);
        $backupJob->handle($progress, $dbService, $backupService);

        $state = $progress->get($backupJobId);
        $zipPath = storage_path('app/backups/' . $state['result']['filename']);
        $this->assertFileExists($zipPath);

        // 2. Modify database and remove image to simulate loss
        Bill::truncate();
        $this->assertEquals(0, Bill::count());
        Storage::disk('public')->delete('bills/original_image.jpg');
        $this->assertFalse(Storage::disk('public')->exists('bills/original_image.jpg'));

        // 3. Run restore job
        $restoreJobId = 'restore_test_' . uniqid();
        $progress->init($restoreJobId, 'restore', 'Test Restore');
        $restoreJob = new RestoreBackupJob($restoreJobId, $zipPath, false);
        $restoreJob->handle($progress, $dbService, $backupService);

        $restoreState = $progress->get($restoreJobId);
        $this->assertEquals('completed', $restoreState['status']);

        // 4. Verify database and images were restored!
        $this->assertEquals(1, Bill::count());
        $restoredBill = Bill::first();
        $this->assertEquals('ORIG-001', $restoredBill->bill_code);
        $this->assertEquals(500.00, $restoredBill->amount);

        // Verify image restored
        $this->assertTrue(Storage::disk('public')->exists('bills/original_image.jpg'));
        $this->assertEquals('original-content', Storage::disk('public')->get('bills/original_image.jpg'));

        // Cleanup
        @unlink($zipPath);
    }

    public function test_chunked_upload_and_assembly()
    {
        $admin = $this->createSuperAdmin();
        $fileId = 'test_chunk_' . uniqid();

        $chunk1 = \Illuminate\Http\UploadedFile::fake()->createWithContent('chunk1.bin', 'PART_ONE_CONTENT_');
        $chunk2 = \Illuminate\Http\UploadedFile::fake()->createWithContent('chunk2.bin', 'PART_TWO_CONTENT');

        // Upload chunk 1 of 2
        $res1 = $this->actingAs($admin)->postJson(route('backup.upload.chunk'), [
            'file_id' => $fileId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'filename' => 'test_large.zip',
            'chunk' => $chunk1,
        ]);
        $res1->assertStatus(200);
        $res1->assertJson(['success' => true, 'completed' => false]);

        // Upload chunk 2 of 2
        $res2 = $this->actingAs($admin)->postJson(route('backup.upload.chunk'), [
            'file_id' => $fileId,
            'chunk_index' => 1,
            'total_chunks' => 2,
            'filename' => 'test_large.zip',
            'chunk' => $chunk2,
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['success' => true, 'completed' => true]);

        $assembledFile = $res2->json('assembled_filename');
        $this->assertNotEmpty($assembledFile);
        $assembledPath = storage_path('app/backups/' . $assembledFile);
        $this->assertFileExists($assembledPath);

        // Verify assembled content
        $this->assertEquals('PART_ONE_CONTENT_PART_TWO_CONTENT', file_get_contents($assembledPath));

        // Cleanup
        @unlink($assembledPath);
    }
}


