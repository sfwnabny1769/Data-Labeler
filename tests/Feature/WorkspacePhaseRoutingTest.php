<?php

namespace Tests\Feature;

use App\Models\WorkspaceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji butir Fase G pada rencana redesign audit workflow:
 *
 *  1. index() & setNickname() me-routing user ke audit.index saat mode preprocess.
 *  2. adminView() me-render partial dinamis sesuai active_activity.
 *  3. updateWorkspaceSettings() memvalidasi & menyimpan multi_labeler_mode.
 *  4. regenerateWorkspacePasskey() menerima custom_passkey dengan fallback random.
 */
class WorkspacePhaseRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function createSetting(string $activity): WorkspaceSetting
    {
        return WorkspaceSetting::create([
            'active_activity' => $activity,
            'access_passkey' => 'TESTPASS123',
            'multi_labeler_mode' => false,
        ]);
    }

    private function userSession(string $passkey = 'TESTPASS123'): array
    {
        return [
            'nickname' => 'UserA',
            'prodi' => 'Informatika',
            'workspace_passkey' => $passkey,
        ];
    }

    /** G.1 — index() redirect ke audit.index saat mode preprocess. */
    public function test_index_redirects_to_audit_when_preprocess_mode_active(): void
    {
        $this->createSetting(WorkspaceSetting::ACTIVE_PREPROCESS);

        $response = $this->withSession($this->userSession())->get(route('home'));

        $response->assertRedirect(route('audit.index'));
    }

    /** G.1 — index() tetap ke halaman labeling saat mode labeling. */
    public function test_index_renders_labeling_when_labeling_mode_active(): void
    {
        $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $response = $this->withSession($this->userSession())->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('label');
    }

    /** G.1 — setNickname() me-routing ke audit.index saat mode preprocess. */
    public function test_set_nickname_redirects_to_audit_when_preprocess_mode_active(): void
    {
        $this->createSetting(WorkspaceSetting::ACTIVE_PREPROCESS);

        $response = $this->from(route('home'))->post(route('nickname.set'), [
            'passkey' => 'TESTPASS123',
            'nickname' => 'UserA',
            'prodi' => 'Informatika',
        ]);

        $response->assertRedirect(route('audit.index'));
        $this->assertSame('UserA', session('nickname'));
    }

    /** G.2 — adminView() me-render partial preprocess saat mode preprocess. */
    public function test_admin_view_renders_preprocess_partial(): void
    {
        $this->createSetting(WorkspaceSetting::ACTIVE_PREPROCESS);

        $response = $this->withSession(['admin_authenticated' => true])->get(route('admin'));

        $response->assertOk();
        $response->assertViewIs('admin');
        $this->assertSame(WorkspaceSetting::ACTIVE_PREPROCESS, $response->viewData('workspaceSetting')->active_activity);
        $this->assertArrayHasKey('round1_pending', $response->viewData('auditStats'));
    }

    /** G.2 — adminView() me-render partial labeling saat mode labeling. */
    public function test_admin_view_renders_labeling_partial(): void
    {
        $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $response = $this->withSession(['admin_authenticated' => true])->get(route('admin'));

        $response->assertOk();
        $this->assertSame(WorkspaceSetting::ACTIVE_LABELING, $response->viewData('workspaceSetting')->active_activity);
        $this->assertArrayHasKey('total', $response->viewData('stats'));
    }
/** G.3 — updateWorkspaceSettings() menyimpan active_activity + multi_labeler_mode. */
    public function test_update_workspace_settings_persists_phase_and_multi_labeler_toggle(): void
    {
        $setting = $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $response = $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.workspace-settings'), [
                'active_activity' => WorkspaceSetting::ACTIVE_PREPROCESS,
                'multi_labeler_mode' => '1',
            ]);

        $response->assertRedirect(route('admin'));
        $response->assertSessionHas('success');

        $setting->refresh();
        $this->assertSame(WorkspaceSetting::ACTIVE_PREPROCESS, $setting->active_activity);
        $this->assertTrue($setting->multi_labeler_mode);
    }

    /** G.3 — activity di luar daftar enum ditolak. */
    public function test_update_workspace_settings_rejects_unknown_activity(): void
    {
        $setting = $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.workspace-settings'), [
                'active_activity' => 'hacked-mode',
            ]);

        $setting->refresh();
        $this->assertSame(WorkspaceSetting::ACTIVE_LABELING, $setting->active_activity);
    }

    /** G.4 — passkey custom disimpan apa adanya (uppercase). */
    public function test_regenerate_passkey_saves_custom_value(): void
    {
        $setting = $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $response = $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.workspace-passkey'), [
                'custom_passkey' => '  tim-satria-2026  ',
            ]);

        $response->assertRedirect(route('admin'));

        $setting->refresh();
        $this->assertSame('TIM-SATRIA-2026', $setting->access_passkey);
    }

    /** G.4 — passkey kosong di-fallback ke random 8 karakter. */
    public function test_regenerate_passkey_falls_back_to_random_when_empty(): void
    {
        $setting = $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.workspace-passkey'), []);

        $setting->refresh();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $setting->access_passkey);
    }

    /** G.4 — passkey custom di luar aturan format ditolak. */
    public function test_regenerate_passkey_rejects_invalid_custom_value(): void
    {
        $setting = $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $this->withSession(['admin_authenticated' => true])
            ->from(route('admin'))
            ->post(route('admin.workspace-passkey'), [
                'custom_passkey' => 'ab',
            ]);

        $setting->refresh();
        $this->assertSame('TESTPASS123', $setting->access_passkey);
    }

    /** Butir C/D — halaman /audit harus bisa dirender (regression: $setting undefined). */
    public function test_audit_index_renders_with_active_activity_data(): void
    {
        $this->createSetting(WorkspaceSetting::ACTIVE_PREPROCESS);

        $response = $this->withSession($this->userSession())->get(route('audit.index'));

        $response->assertOk();
        $response->assertViewIs('audit');
        $this->assertSame(WorkspaceSetting::ACTIVE_PREPROCESS, $response->viewData('activeActivity'));
        $this->assertNotEmpty($response->viewData('classOptions'));
    }

    /** Butir C — relabel_to harus dinamis dari config, bukan hardcode. */
    public function test_submit_decision_accepts_config_driven_relabel_class(): void
    {
        config(['competition.classes' => [
            ['id' => 0, 'name' => 'Aman', 'badge' => 'Daur Ulang (Label: 0)'],
            ['id' => 1, 'name' => 'Rusak', 'badge' => 'Elektronik (Label: 1)'],
        ]]);

        $this->createSetting(WorkspaceSetting::ACTIVE_PREPROCESS);

        $candidate = \App\Models\AuditCandidate::create([
            'filename' => 'dinamis_test.jpg',
            'given_label' => '0_Aman',
            'predicted_label' => '1_Rusak',
            'label_quality_score' => 0.1,
            'priority_score' => 0.9,
        ]);

        $response = $this->withSession($this->userSession())
            ->postJson(route('api.audit.submit'), [
                'candidate_id' => $candidate->id,
                'decision' => 'A',
                'relabel_to' => '1_Rusak',
            ]);

        $response->assertStatus(200);

        $candidate->refresh();
        $this->assertSame('A', $candidate->round1_decision);
        $this->assertSame('1_Rusak', $candidate->round2_decision);
    }

    /** Endpoint passkey tidak bisa diakses tanpa login admin. */
    public function test_regenerate_passkey_requires_admin_authentication(): void
    {
        $setting = $this->createSetting(WorkspaceSetting::ACTIVE_LABELING);

        $this->post(route('admin.workspace-passkey'), ['custom_passkey' => 'NEW-PASSKEY'])
            ->assertRedirect(route('admin'));

        $setting->refresh();
        $this->assertSame('TESTPASS123', $setting->access_passkey);
    }
}