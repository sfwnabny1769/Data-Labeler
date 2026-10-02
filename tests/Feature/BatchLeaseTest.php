<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\WorkspaceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchLeaseTest extends TestCase
{
    use RefreshDatabase;

    private function setupActiveSession(string $nickname = 'Tester1'): array
    {
        $setting = WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
        ]);

        return [
            'nickname' => $nickname,
            'prodi' => 'Informatika',
            'workspace_passkey' => 'TESTPASS123',
        ];
    }

    public function test_batch_allocation_reserves_images_with_lease(): void
    {
        $session = $this->setupActiveSession('UserA');

        // Create 5 unlabeled images
        for ($i = 1; $i <= 5; $i++) {
            Image::create([
                'filename' => "sample_{$i}.jpg",
                'label_status' => 'unlabeled',
            ]);
        }

        // User A requests a batch of 3
        $responseA = $this->withSession($session)
            ->getJson(route('api.batch-images', ['count' => 3]));

        $responseA->assertStatus(200);
        $responseA->assertJsonCount(3, 'images');

        // Verify those 3 are reserved by UserA in DB
        $reservedCount = Image::where('reserved_by', 'UserA')->count();
        $this->assertEquals(3, $reservedCount);

        // User B requests a batch of 3
        $sessionB = [
            'nickname' => 'UserB',
            'prodi' => 'Sistem Informasi',
            'workspace_passkey' => 'TESTPASS123',
        ];

        $responseB = $this->withSession($sessionB)
            ->getJson(route('api.batch-images', ['count' => 3]));

        $responseB->assertStatus(200);
        // Only 2 remaining images are free, so User B should only get 2
        $responseB->assertJsonCount(2, 'images');

        // Ensure no overlapping images between User A and User B
        $idsA = collect($responseA->json('images'))->pluck('id')->toArray();
        $idsB = collect($responseB->json('images'))->pluck('id')->toArray();
        $overlap = array_intersect($idsA, $idsB);
        $this->assertEmpty($overlap, 'User A and User B should never receive the same images due to lease locking');
    }

    public function test_release_lease_frees_images_back_to_public_pool(): void
    {
        $session = $this->setupActiveSession('UserA');

        $img = Image::create([
            'filename' => 'release_test.jpg',
            'label_status' => 'unlabeled',
            'reserved_by' => 'UserA',
            'reserved_until' => now()->addMinutes(5),
        ]);

        $response = $this->withSession($session)
            ->postJson(route('api.release-lease'), [
                'image_ids' => [$img->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $img->refresh();
        $this->assertNull($img->reserved_by);
        $this->assertNull($img->reserved_until);
    }

    public function test_submit_label_clears_lease_and_sets_pending(): void
    {
        $session = $this->setupActiveSession('UserA');

        $img = Image::create([
            'filename' => 'submit_test.jpg',
            'label_status' => 'unlabeled',
            'reserved_by' => 'UserA',
            'reserved_until' => now()->addMinutes(5),
        ]);

        $response = $this->withSession($session)
            ->postJson(route('api.submit-label'), [
                'image_id' => $img->id,
                'label' => 0,
            ]);

        $response->assertStatus(200);

        $img->refresh();
        $this->assertEquals(0, $img->label);
        $this->assertEquals('pending', $img->label_status);
        $this->assertEquals('UserA', $img->labeled_by);
        $this->assertNull($img->reserved_by);
        $this->assertNull($img->reserved_until);
    }
}
