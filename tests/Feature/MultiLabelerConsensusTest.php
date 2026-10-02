<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\ImageLabel;
use App\Models\WorkspaceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji Butir 9: multi-labeler per gambar + antrean resolusi dispute.
 *
 * Skenario inti:
 *  - required_labelers = 2 secara default.
 *  - Satu suara pertama  -> status 'collecting' (menunggu labeler lain).
 *  - Suara kedua sama    -> consensus unanimous, auto 'approved'.
 *  - Suara kedua beda    -> status 'dispute', masuk antrean admin.
 *  - Labeler yang sama tidak boleh memberi suara dua kali.
 *  - Alokasi batch tidak boleh memberi gambar yang sudah divot user itu.
 */
class MultiLabelerConsensusTest extends TestCase
{
    use RefreshDatabase;

    private function enableMultiLabeler(int $required = 2): WorkspaceSetting
    {
        config(['competition.required_labelers' => $required]);

        return WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => true,
        ]);
    }

    private function userSession(string $nickname): array
    {
        return [
            'nickname' => $nickname,
            'prodi' => 'Informatika',
            'workspace_passkey' => 'TESTPASS123',
        ];
    }

    private function makeImage(): Image
    {
        return Image::create([
            'filename' => 'multi_test.jpg',
            'label_status' => 'unlabeled',
        ]);
    }

    /** Satu suara -> 'collecting', consensus belum dihitung. */
    public function test_first_vote_keeps_image_in_collecting_state(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $response = $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), [
                'image_id' => $image->id,
                'label' => 0,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'multi_labeler' => true,
            'status' => 'collecting',
            'votes' => 1,
            'required' => 2,
        ]);

        $image->refresh();
        $this->assertSame('collecting', $image->label_status);
        $this->assertNull($image->label, 'Label final belum boleh diisi sebelum cukup suara');
        $this->assertDatabaseHas('image_labels', [
            'image_id' => $image->id,
            'labeled_by' => 'UserA',
            'label' => 0,
        ]);
    }

    /** Suara kedua yang sama -> auto-approved tanpa intervensi admin. */
    public function test_matching_second_vote_triggers_unanimous_consensus(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 1]);

        $response = $this->withSession($this->userSession('UserB'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 1]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'approved']);

        $image->refresh();
        $this->assertSame('approved', $image->label_status);
        $this->assertSame(1, (int) $image->label);
        $this->assertDatabaseCount('image_labels', 2);
    }

    /** Suara kedua yang berbeda -> status 'dispute', menunggu admin. */
    public function test_conflicting_second_vote_creates_dispute(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 0]);

        $response = $this->withSession($this->userSession('UserB'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 2]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'dispute']);

        $image->refresh();
        $this->assertSame('dispute', $image->label_status);
        $this->assertNull($image->label, 'Label final tidak boleh diisi saat dispute');
    }

    /** Labeler yang sama tidak boleh memberi suara dua kali. */
    public function test_same_labeler_cannot_vote_twice(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 0])
            ->assertStatus(200);

        $response = $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 2]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('image_labels', 1);

        $image->refresh();
        $this->assertSame('collecting', $image->label_status);
    }

    /** Batch tidak boleh mengalokasikan gambar yang sudah divot user tersebut. */
    public function test_batch_allocation_skips_images_user_already_voted(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 0])
            ->assertStatus(200);

        // UserA minta batch lagi: gambar yang sudah ia voti harus dilewati.
        $response = $this->withSession($this->userSession('UserA'))
            ->getJson(route('api.batch-images', ['count' => 5]));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'images');

        // UserB (belum pernah voti) tetap boleh mengambil gambar yang sama.
        $responseB = $this->withSession($this->userSession('UserB'))
            ->getJson(route('api.batch-images', ['count' => 5]));

        $responseB->assertStatus(200);
        $responseB->assertJsonCount(1, 'images');
    }

    /** Admin menyelesaikan dispute dengan memilih label final. */
    public function test_admin_can_resolve_dispute(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 0]);
        $this->withSession($this->userSession('UserB'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 2]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.dispute.resolve', $image->id), [
                'label' => 0,
                'dispute_note' => 'Setelah dicek manual, gambar jelas kategori 0.',
            ]);

        $response->assertRedirect(route('admin'));

        $image->refresh();
        $this->assertSame('approved', $image->label_status);
        $this->assertSame(0, (int) $image->label);
        $this->assertSame('Setelah dicek manual, gambar jelas kategori 0.', $image->dispute_note);
    }

    /** Admin membuka kembali dispute -> suara dihapus, gambar balik ke antrean. */
    public function test_admin_can_reopen_dispute(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 0]);
        $this->withSession($this->userSession('UserB'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 2]);

        $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.dispute.reopen', $image->id));

        $image->refresh();
        $this->assertSame('unlabeled', $image->label_status);
        $this->assertNull($image->label);
        $this->assertNull($image->dispute_note);
        $this->assertDatabaseCount('image_labels', 0);
    }

    /** Endpoint dispute wajib dilindungi login admin. */
    public function test_dispute_resolution_requires_admin_authentication(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();
        $image->update(['label_status' => 'dispute']);

        $this->post(route('admin.dispute.resolve', $image->id), ['label' => 0])
            ->assertRedirect(route('admin'));

        $image->refresh();
        $this->assertSame('dispute', $image->label_status);
    }

    /** Panel admin menampilkan antrean dispute & metrik agreement. */
    public function test_admin_panel_exposes_dispute_queue_and_stats(): void
    {
        $this->enableMultiLabeler(2);
        $image = $this->makeImage();
        $image->update(['label_status' => 'dispute', 'label' => null]);
        ImageLabel::create(['image_id' => $image->id, 'labeled_by' => 'UserA', 'label' => 0]);
        ImageLabel::create(['image_id' => $image->id, 'labeled_by' => 'UserB', 'label' => 2]);

        $response = $this->withSession(['admin_authenticated' => true])->get(route('admin'));

        $response->assertOk();

        $disputeItems = $response->viewData('disputeItems');
        $this->assertCount(1, $disputeItems);
        $this->assertCount(2, $disputeItems->first()->votes);

        $disputeStats = $response->viewData('disputeStats');
        $this->assertSame(1, $disputeStats['dispute']);
        $this->assertSame(2, $disputeStats['total_votes']);
        $this->assertSame(2, $disputeStats['required_labelers']);
        $this->assertTrue($response->viewData('multiLabelerMode'));

        // Panel dispute harus benar-benar ter-render di halaman admin.
        $response->assertSee('Antrean Resolution Dispute');
        $response->assertSee(route('admin.dispute.resolve', $image->id), false);
        $response->assertSee(route('admin.dispute.reopen', $image->id), false);
    }

    /** Panel dispute tidak boleh muncul saat mode single-labeler. */
    public function test_dispute_panel_hidden_when_multi_labeler_off(): void
    {
        WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);

        $response = $this->withSession(['admin_authenticated' => true])->get(route('admin'));

        $response->assertOk();
        $response->assertDontSee('Antrean Resolution Dispute');
    }

    /** Tema Neobrutalism terpasang di semua halaman utama. */
    public function test_neobrutalism_theme_is_applied(): void
    {
        WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);

        // Halaman admin
        $admin = $this->withSession(['admin_authenticated' => true])->get(route('admin'));
        $admin->assertSee('--nb-ink', false);

        // Halaman labeler
        $label = $this->withSession($this->userSession('UserA'))->get(route('home'));
        $label->assertSee('--nb-ink', false);
    }

    /**
     * Regression: overlay berlatar gelap (mis. layar "Semua Gambar Selesai
     * Dilabel!") harus memaksa teks heading jadi putih. Tanpa aturan ini,
     * tema Neobrutalism yang memaksa semua teks jadi hitam membuat heading
     * putih hilang (hitam di atas latar hitam).
     */
    public function test_dark_overlay_forces_white_heading_text(): void
    {
        WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);

        $response = $this->withSession($this->userSession('UserA'))->get(route('home'));

        $response->assertOk();

        // Overlay gelap + teks heading putih harus tetap ada di halaman.
        $response->assertSee('bg-slate-950/95', false);
        $response->assertSee('Semua Gambar Selesai Dilabel!');

        // Aturan CSS yang mengembalikkan teks jadi putih wajib terpasang.
        $response->assertSee('webkit-text-fill-color: #ffffff', false);
    }

    /**
     * Regression: halaman labeler & halaman masuk harus pakai class penanda
     * (app-labeler / app-gate) yang dipakai rules fit-layout di tema.
     *
     * Tanpa class ini, layout memakai min-h-[520px] + min-h-[340px] yang
     * memaksa halaman melebihi tinggi layar laptop 1366x768, sehingga user
     * harus scroll atau zoom out untuk menekan tombol.
     */
    public function test_fit_layout_markers_are_applied(): void
    {
        WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);

        // Halaman masuk (gate) tanpa session -> view nickname
        $gate = $this->get(route('home'));
        $gate->assertOk();
        $gate->assertViewIs('nickname');
        $gate->assertSee('app-gate', false);

        // Halaman labeler dengan session valid
        $labeler = $this->withSession($this->userSession('UserA'))->get(route('home'));
        $labeler->assertOk();
        $labeler->assertViewIs('label');
        $labeler->assertSee('app-labeler', false);

        // Rules fit-layout harus ikut ter-render
        $labeler->assertSee('body.app-labeler main', false);
        $labeler->assertSee('min-height: 100dvh', false);
    }

    /** Rules responsif untuk layar pendek & mobile harus ada. */
    public function test_responsive_fit_rules_present(): void
    {
        WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);

        $response = $this->withSession($this->userSession('UserA'))->get(route('home'));

        // Layar pendek (laptop kecil)
        $response->assertSee('max-height: 800px', false);
        // Mobile
        $response->assertSee('max-width: 1023px', false);
        // Halaman masuk punya rules sendiri
        $response->assertSee('body.app-gate', false);
    }

    /** Mode single-labeler (default) tidak boleh ikut terubah oleh fitur ini. */
    public function test_single_labeler_mode_keeps_legacy_behaviour(): void
    {
        WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);

        $image = $this->makeImage();

        $this->withSession($this->userSession('UserA'))
            ->postJson(route('api.submit-label'), ['image_id' => $image->id, 'label' => 2])
            ->assertJson(['success' => true]);

        $image->refresh();
        $this->assertSame('pending', $image->label_status);
        $this->assertSame(2, (int) $image->label);
        $this->assertDatabaseCount('image_labels', 0);
    }
}
