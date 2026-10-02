<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DatasetController;
use App\Http\Controllers\AuditController;

// User routes
Route::get('/', [DatasetController::class, 'index'])->name('home');
Route::post('/nickname', [DatasetController::class, 'setNickname'])->name('nickname.set');
Route::post('/logout-nickname', [DatasetController::class, 'logoutNickname'])->name('nickname.logout');
Route::get('/dataset-image/{filename}', [DatasetController::class, 'serveImage'])->name('images.show');

// User API routes
Route::get('/api/next-image', [DatasetController::class, 'getNextImage'])->name('api.next-image');
Route::get('/api/images/batch', [DatasetController::class, 'getBatchImages'])->name('api.batch-images');
Route::post('/api/images/release-lease', [DatasetController::class, 'releaseLease'])->name('api.release-lease');
Route::post('/api/images/heartbeat', [DatasetController::class, 'heartbeatLease'])->name('api.heartbeat-lease');
Route::post('/api/submit-label', [DatasetController::class, 'submitLabel'])->name('api.submit-label');
Route::post('/api/undo-label', [DatasetController::class, 'undoLastLabel'])->name('api.undo-label');
Route::get('/api/leaderboard', [DatasetController::class, 'getLeaderboard'])->name('api.leaderboard');

// Admin routes
Route::get('/admin', [DatasetController::class, 'adminView'])->name('admin');
Route::post('/admin/login', [DatasetController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/logout', [DatasetController::class, 'adminLogout'])->name('admin.logout');
Route::post('/admin/sync', [DatasetController::class, 'syncDataset'])->name('admin.sync');
Route::post('/admin/approve/{id}', [DatasetController::class, 'approveLabel'])->name('admin.approve');
Route::post('/admin/reject/{id}', [DatasetController::class, 'rejectLabel'])->name('admin.reject');
Route::post('/admin/update/{id}', [DatasetController::class, 'updateLabel'])->name('admin.update');
Route::post('/admin/approve-all', [DatasetController::class, 'approveAll'])->name('admin.approve-all');
Route::post('/admin/reject-all', [DatasetController::class, 'rejectAll'])->name('admin.reject-all');
Route::post('/admin/reject-all-pending', [DatasetController::class, 'rejectAllPending'])->name('admin.reject-all-pending');
Route::get('/admin/download', [DatasetController::class, 'downloadCsv'])->name('admin.download');
Route::get('/admin/download/split', [DatasetController::class, 'downloadTrainValSplit'])->name('admin.download-split');
Route::get('/admin/download/full', [DatasetController::class, 'downloadFullCsv'])->name('admin.download-full');
Route::post('/admin/leases/release-user', [DatasetController::class, 'adminReleaseUserLease'])->name('admin.leases.release-user');
Route::post('/admin/leases/release-all', [DatasetController::class, 'adminReleaseAllLeases'])->name('admin.leases.release-all');
Route::post('/admin/upload-dataset', [DatasetController::class, 'uploadDataset'])->name('admin.upload-dataset');
Route::post('/admin/upload-examples', [DatasetController::class, 'uploadExamples'])->name('admin.upload-examples');
Route::post('/admin/delete-example', [DatasetController::class, 'deleteExample'])->name('admin.delete-example');
Route::post('/admin/workspace/settings', [DatasetController::class, 'updateWorkspaceSettings'])->name('admin.workspace-settings');
Route::post('/admin/workspace/passkey', [DatasetController::class, 'regenerateWorkspacePasskey'])->name('admin.workspace-passkey');

// Butir 9: resolusi dispute multi-labeler
Route::post('/admin/dispute/resolve/{id}', [DatasetController::class, 'resolveDispute'])->name('admin.dispute.resolve');
Route::post('/admin/dispute/reopen/{id}', [DatasetController::class, 'reopenDispute'])->name('admin.dispute.reopen');

// Audit routes -- putaran review kandidat salah-label (hasil scripts/audit_data.py)
Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
Route::get('/audit/relabel', [AuditController::class, 'relabelIndex'])->name('audit.relabel');
Route::get('/audit-image/{label}/{filename}', [AuditController::class, 'serveImage'])->name('audit.image');

Route::get('/api/audit/next', [AuditController::class, 'getNextCandidate'])->name('api.audit.next');
Route::post('/api/audit/submit', [AuditController::class, 'submitDecision'])->name('api.audit.submit');
Route::post('/api/audit/crop', [AuditController::class, 'cropImage'])->name('api.audit.crop');
Route::get('/api/audit/relabel/next', [AuditController::class, 'getNextRelabel'])->name('api.audit.relabel.next');
Route::post('/api/audit/relabel/submit', [AuditController::class, 'submitRelabel'])->name('api.audit.relabel.submit');

// Audit admin routes
// Catatan: panel audit sudah dilebur ke halaman admin utama (single-page).
// /admin/audit GET sekarang redirect ke /admin agar bookmark lama tetap valid.
Route::get('/admin/audit', function () {
    return redirect()->route('admin');
})->name('admin.audit');
Route::post('/admin/audit/upload-csv', [AuditController::class, 'uploadCandidatesCsv'])->name('admin.audit.upload-csv');
Route::post('/admin/audit/upload-train-zip', [AuditController::class, 'uploadTrainZip'])->name('admin.audit.upload-train-zip');
Route::get('/admin/audit/download/round1', [AuditController::class, 'downloadRound1Csv'])->name('admin.audit.download-round1');
Route::get('/admin/audit/download/round2', [AuditController::class, 'downloadRound2Csv'])->name('admin.audit.download-round2');
