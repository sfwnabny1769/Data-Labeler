<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditCandidate;
use App\Models\Image;
use App\Models\ImageLabel;
use App\Models\WorkspaceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use ZipArchive;

class DatasetController extends Controller
{
    private function getWorkspaceSetting(): WorkspaceSetting
    {
        $setting = WorkspaceSetting::query()->first();

        if ($setting) {
            return $setting;
        }

        return WorkspaceSetting::create([
            'active_activity' => WorkspaceSetting::ACTIVE_LABELING,
            'access_passkey' => strtoupper(Str::random(8)),
        ]);
    }

    private function isWorkspaceAccessValid(Request $request, ?string $expectedActivity = null): bool
    {
        $setting = $this->getWorkspaceSetting();
        $sessionPasskey = (string) $request->session()->get('workspace_passkey', '');

        if ($sessionPasskey === '' || !hash_equals((string) $setting->access_passkey, $sessionPasskey)) {
            return false;
        }

        if ($expectedActivity !== null && $setting->active_activity !== $expectedActivity) {
            return false;
        }

        return true;
    }

    private function deniedWorkspaceResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('home')->with('error', $message);
    }

    /**
     * Get the dataset path from configuration.
     */
    private function getDatasetPath()
    {
        $path = env('DATASET_PATH');
        if (!$path) {
            // Check if public/dataset/test exists (e.g. from Kaggle dataset structure), else fallback to public/dataset
            $testPath = public_path('dataset' . DIRECTORY_SEPARATOR . 'test');
            $path = File::exists($testPath) ? $testPath : public_path('dataset');
        }
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Landing page for setting nickname or redirecting to labeling interface.
     */
    public function index(Request $request)
    {
        $setting = $this->getWorkspaceSetting();

        if ($request->session()->has('nickname') && $this->isWorkspaceAccessValid($request, WorkspaceSetting::ACTIVE_LABELING)) {
            $classes = config('competition.classes', [
                ['id' => 0, 'name' => 'Aman', 'badge' => 'Daur Ulang (Label: 0)', 'shortcut_label' => 'Q / 1', 'keys' => ['0', '1', 'q', 'Q'], 'color' => 'emerald', 'desc' => 'Daur Ulang', 'icon' => 'recycle'],
                ['id' => 1, 'name' => 'Rusak', 'badge' => 'Elektronik (Label: 1)', 'shortcut_label' => 'W / 2', 'keys' => ['2', 'w', 'W'], 'color' => 'blue', 'desc' => 'Elektronik', 'icon' => 'bolt'],
                ['id' => 2, 'name' => 'Lainnya', 'badge' => 'Organik (Label: 2)', 'shortcut_label' => 'E / 3', 'keys' => ['3', 'e', 'E'], 'color' => 'amber', 'desc' => 'Organik', 'icon' => 'sparkles'],
            ]);

            $examples = [];
            foreach ($classes as $cls) {
                $examples[$cls['id']] = [];
            }

            // 1. Coba ambil dari database ExampleImage (yang diunggah ke volume persisten)
            foreach (array_keys($examples) as $label) {
                $dbExamples = \App\Models\ExampleImage::where('label', $label)->get();
                if ($dbExamples->isNotEmpty()) {
                    foreach ($dbExamples as $dbEx) {
                        $examples[$label][] = $dbEx->url;
                    }
                }
            }

            // 2. Jika folder manual kosong, baru fallback ke folder train dataset (public/dataset/train/)
            $trainPath = public_path('dataset' . DIRECTORY_SEPARATOR . 'train');
            if (File::exists($trainPath)) {
                $directories = File::directories($trainPath);
                
                foreach (array_keys($examples) as $label) {
                    if (empty($examples[$label])) {
                        $targetDir = null;
                        foreach ($directories as $dir) {
                            $dirName = basename($dir);
                            if (str_starts_with($dirName, $label . '_')) {
                                $targetDir = $dir;
                                break;
                            }
                        }

                        if ($targetDir && File::exists($targetDir)) {
                            $files = File::files($targetDir);
                            $imageFiles = array_filter($files, function($file) {
                                return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                            });

                            if (!empty($imageFiles)) {
                                shuffle($imageFiles);
                                $selectedFiles = array_slice($imageFiles, 0, 4);

                                foreach ($selectedFiles as $file) {
                                    $folderName = basename($targetDir);
                                    $examples[$label][] = asset("dataset/train/{$folderName}/" . $file->getFilename());
                                }
                            }
                        }
                    }
                }
            }

            return view('label', [
                'nickname' => $request->session()->get('nickname'),
                'prodi' => $request->session()->get('prodi'),
                'examples' => $examples,
                'passkey' => $setting->access_passkey,
                'classes' => $classes,
                'competitionName' => config('competition.name', 'Data Labeler Workspace'),
                'leaseDuration' => (int) config('competition.lease_duration_minutes', 3),
                'batchSize' => (int) config('competition.buffer_batch_size', 6),
            ]);
        }

        // Audit UI dipakai di mode Fase 0 (preprocess) atau Fase 2 (audit hasil labeling).
        $auditModes = [WorkspaceSetting::ACTIVE_PREPROCESS, WorkspaceSetting::ACTIVE_AUDIT];

        if ($request->session()->has('nickname') && $this->isWorkspaceAccessValid($request) && in_array($setting->active_activity, $auditModes, true)) {
            return redirect()->route('audit.index');
        }

        if ($request->session()->has('nickname')) {
            $request->session()->forget(['nickname', 'prodi', 'workspace_passkey']);
        }

        return view('nickname', [
            'passkey' => $setting->access_passkey,
            'activeActivity' => $setting->active_activity,
        ]);
    }

    /**
     * Set user nickname and prodi in session.
     */
    public function setNickname(Request $request)
    {
        $setting = $this->getWorkspaceSetting();

        $request->validate([
            'passkey' => 'required|string|max:50',
            'nickname' => 'required|string|max:50|regex:/^[a-zA-Z0-9_\s\-]+$/',
            'prodi' => 'required|string|max:100|regex:/^[a-zA-Z0-9_\s\-\.]+$/'
        ], [
            'passkey.required' => 'Passkey wajib diisi.',
            'nickname.regex' => 'Nama/nickname hanya boleh mengandung huruf, angka, spasi, garis bawah, dan tanda hubung.',
            'prodi.regex' => 'Program studi hanya boleh mengandung huruf, angka, spasi, titik, garis bawah, dan tanda hubung.'
        ]);

        if (!hash_equals((string) $setting->access_passkey, (string) $request->input('passkey'))) {
            return back()->withErrors(['passkey' => 'Passkey salah. Gunakan passkey aktif dari admin.'])->withInput();
        }

        $nickname = trim($request->input('nickname'));
        $prodi = trim($request->input('prodi'));

        $request->session()->put('nickname', $nickname);
        $request->session()->put('prodi', $prodi);
        $request->session()->put('workspace_passkey', $request->input('passkey'));

        $auditModes = [WorkspaceSetting::ACTIVE_PREPROCESS, WorkspaceSetting::ACTIVE_AUDIT];
        if (in_array($setting->active_activity, $auditModes, true)) {
            return redirect()->route('audit.index');
        }

        return redirect()->route('home');
    }

    /**
     * Clear nickname and prodi session.
     */
    public function logoutNickname(Request $request)
    {
        $request->session()->forget(['nickname', 'prodi', 'workspace_passkey']);
        return redirect()->route('home');
    }

    /**
     * Serve an image file from the dataset path.
     */
    /**
     * Serve an image file from the dataset path with immutable caching for max performance.
     */
    public function serveImage($filename)
    {
        // Prevent directory traversal attacks
        $filename = basename($filename);
        $datasetPath = $this->getDatasetPath();
        $filePath = $datasetPath . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($filePath)) {
            abort(404, 'Image not found in dataset folder.');
        }

        // Stream file directly with immutable long cache so browser never re-queries it
        return response()->file($filePath, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Get a batch of unlabeled images with atomic reservation (lease) for zero-latency client buffer.
     */
    public function getBatchImages(Request $request)
    {
        if (!$this->isWorkspaceAccessValid($request, WorkspaceSetting::ACTIVE_LABELING)) {
            return $this->deniedWorkspaceResponse($request, 'Workspace labeling belum aktif atau passkey sudah tidak valid.');
        }

        $nickname = (string) $request->session()->get('nickname');
        $batchSize = (int) $request->input('count', config('competition.buffer_batch_size', 6));
        $batchSize = max(1, min(12, $batchSize));
        $excludeIds = (array) $request->input('exclude_ids', []);
        $multiLabeler = (bool) $this->getWorkspaceSetting()->multi_labeler_mode;

        $leaseMinutes = (int) config('competition.lease_duration_minutes', 3);
        $leaseExpiry = now()->addMinutes($leaseMinutes);

        // Atomic reservation using database transaction
        $allocatedImages = DB::transaction(function () use ($batchSize, $excludeIds, $nickname, $leaseExpiry, $multiLabeler) {
            $candidateQuery = Image::where(function ($query) {
                $query->whereNull('reserved_until')
                      ->orWhere('reserved_until', '<', now());
            });

            if ($multiLabeler) {
                // Mode multi-labeler: gambar 'collecting' masih butuh suara tambahan,
                // jadi tetap boleh dialokasikan -- tapi TIDAK ke labeler yang sudah
                // memberi suara untuk gambar tersebut.
                $candidateQuery->whereIn('label_status', ['unlabeled', 'collecting'])
                    ->whereNotIn('id', function ($sub) use ($nickname) {
                        $sub->select('image_id')
                            ->from('image_labels')
                            ->where('labeled_by', $nickname);
                    });
            } else {
                $candidateQuery->where('label_status', 'unlabeled');
            }

            if (!empty($excludeIds)) {
                $candidateQuery->whereNotIn('id', $excludeIds);
            }

            // Lock and acquire candidate IDs
            $candidateIds = $candidateQuery->lockForUpdate()
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();

            if (!empty($candidateIds)) {
                Image::whereIn('id', $candidateIds)->update([
                    'reserved_by' => $nickname,
                    'reserved_until' => $leaseExpiry,
                ]);

                return Image::whereIn('id', $candidateIds)->get();
            }

            return collect();
        });

        // Fast statistics
        $stats = Image::selectRaw("
            COUNT(CASE WHEN label_status = 'unlabeled' THEN 1 END) as total_left,
            COUNT(CASE WHEN label_status IN ('pending', 'approved') THEN 1 END) as total_labeled,
            COUNT(CASE WHEN labeled_by = ? AND label_status IN ('pending', 'approved') THEN 1 END) as user_labeled
        ", [$nickname])->first();

        $imagesList = $allocatedImages->map(function ($img) {
            return [
                'id' => $img->id,
                'filename' => $img->filename,
                'url' => $img->url,
            ];
        });

        return response()->json([
            'completed' => $imagesList->isEmpty() && ((int) $stats->total_left === 0),
            'images' => $imagesList,
            'count' => $imagesList->count(),
            'total_left' => (int) $stats->total_left,
            'total_labeled' => (int) $stats->total_labeled,
            'user_labeled' => (int) $stats->user_labeled,
            'lease_expires_at' => $leaseExpiry->toIso8601String(),
        ]);
    }

    /**
     * Release lease for images held by user (called on page unload / disconnect).
     */
    public function releaseLease(Request $request)
    {
        $nickname = (string) $request->session()->get('nickname', $request->input('nickname'));
        $imageIds = (array) $request->input('image_ids', []);

        if (empty($nickname)) {
            return response()->json(['success' => false, 'message' => 'No active user session'], 400);
        }

        $query = Image::where('label_status', 'unlabeled')
            ->where('reserved_by', $nickname);

        if (!empty($imageIds)) {
            $query->whereIn('id', $imageIds);
        }

        $released = $query->update([
            'reserved_by' => null,
            'reserved_until' => null,
        ]);

        return response()->json(['success' => true, 'released' => $released]);
    }

    /**
     * Extend lease for active images currently in user's buffer.
     */
    public function heartbeatLease(Request $request)
    {
        $nickname = (string) $request->session()->get('nickname');
        $imageIds = (array) $request->input('image_ids', []);

        if (empty($nickname) || empty($imageIds)) {
            return response()->json(['success' => false], 400);
        }

        $leaseMinutes = (int) config('competition.lease_duration_minutes', 3);
        $newExpiry = now()->addMinutes($leaseMinutes);

        Image::where('label_status', 'unlabeled')
            ->where('reserved_by', $nickname)
            ->whereIn('id', $imageIds)
            ->update([
                'reserved_until' => $newExpiry,
            ]);

        return response()->json(['success' => true, 'lease_expires_at' => $newExpiry->toIso8601String()]);
    }

    /**
     * Get the next available unlabeled image for labeling (single fallback).
     */
    public function getNextImage(Request $request)
    {
        if (!$this->isWorkspaceAccessValid($request, WorkspaceSetting::ACTIVE_LABELING)) {
            return $this->deniedWorkspaceResponse($request, 'Workspace labeling belum aktif atau passkey sudah tidak valid.');
        }

        $nickname = $request->session()->get('nickname');
        $excludeId = $request->integer('exclude_id');

        $leaseMinutes = (int) config('competition.lease_duration_minutes', 3);
        $leaseExpiry = now()->addMinutes($leaseMinutes);

        // Calculate all statistics in a single SQL query
        $stats = Image::selectRaw("
            COUNT(CASE WHEN label_status = 'unlabeled' THEN 1 END) as total_left,
            COUNT(CASE WHEN label_status IN ('pending', 'approved') THEN 1 END) as total_labeled,
            COUNT(CASE WHEN labeled_by = ? AND label_status IN ('pending', 'approved') THEN 1 END) as user_labeled
        ", [$nickname])->first();

        $totalLeft = (int) $stats->total_left;
        $totalLabeled = (int) $stats->total_labeled;
        $userLabeled = (int) $stats->user_labeled;

        $image = DB::transaction(function () use ($excludeId, $nickname, $leaseExpiry) {
            $query = Image::where('label_status', 'unlabeled')
                ->where(function ($q) {
                    $q->whereNull('reserved_until')
                      ->orWhere('reserved_until', '<', now());
                });

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $img = $query->lockForUpdate()->first();
            if ($img) {
                $img->update([
                    'reserved_by' => $nickname,
                    'reserved_until' => $leaseExpiry,
                ]);
            }
            return $img;
        });

        if (!$image) {
            return response()->json([
                'completed' => true,
                'total_left' => 0,
                'total_labeled' => $totalLabeled,
                'user_labeled' => $userLabeled
            ]);
        }

        return response()->json([
            'completed' => false,
            'image' => [
                'id' => $image->id,
                'filename' => $image->filename,
                'url' => $image->url
            ],
            'total_left' => $totalLeft,
            'total_labeled' => $totalLabeled,
            'user_labeled' => $userLabeled
        ]);
    }

    /**
     * Submit a label for an image with dynamic class validation & lease clearance.
     */
    public function submitLabel(Request $request)
    {
        if (!$this->isWorkspaceAccessValid($request, WorkspaceSetting::ACTIVE_LABELING)) {
            return $this->deniedWorkspaceResponse($request, 'Workspace labeling belum aktif atau passkey sudah tidak valid.');
        }

        $nickname = $request->session()->get('nickname');
        $prodi = $request->session()->get('prodi');

        $validClassIds = array_column(config('competition.classes', []), 'id');
        if (empty($validClassIds)) {
            $validClassIds = [0, 1, 2];
        }

        $request->validate([
            'image_id' => 'required|exists:images,id',
            'label' => ['required', \Illuminate\Validation\Rule::in($validClassIds)],
        ]);

        $imageId = $request->input('image_id');
        $label = $request->input('label');

        // Butir 9: mode multi-labeler butuh lebih dari satu suara per gambar.
        // Vote dicatat terpisah di image_labels; consensus baru ditulis ke images.label.
        $multiLabeler = (bool) $this->getWorkspaceSetting()->multi_labeler_mode;

        $result = DB::transaction(function () use ($imageId, $label, $nickname, $prodi, $multiLabeler) {
            $required = max(1, (int) config('competition.required_labelers', 2));

            $query = Image::where('id', $imageId)->lockForUpdate();

            // Di mode single-labeler, gambar langsung keluar dari antrean publik
            // setelah dilabeli (status bukan lagi 'unlabeled'). Di mode multi-labeler
            // gambar tetap tersedia sampai cukup suara terkumpul.
            if (!$multiLabeler) {
                $query->where('label_status', 'unlabeled');
            } else {
                $query->where(function ($q) {
                    $q->where('label_status', 'unlabeled')
                      ->orWhere('label_status', 'collecting');
                });
            }

            $image = $query->first();

            if (!$image) {
                return ['ok' => false, 'reason' => 'conflict'];
            }

            if ($multiLabeler) {
                // Cegah labeler yang sama memberi suara dua kali pada gambar yang sama.
                $alreadyVoted = ImageLabel::where('image_id', $image->id)
                    ->where('labeled_by', $nickname)
                    ->exists();

                if ($alreadyVoted) {
                    return ['ok' => false, 'reason' => 'already_voted'];
                }

                ImageLabel::create([
                    'image_id' => $image->id,
                    'labeled_by' => $nickname,
                    'prodi' => $prodi,
                    'label' => $label,
                ]);

                $votes = ImageLabel::where('image_id', $image->id)->get();
                $distinctLabels = $votes->pluck('label')->unique();

                $image->reserved_by = null;
                $image->reserved_until = null;

                if ($votes->count() >= $required) {
                    if ($distinctLabels->count() === 1) {
                        // Consensus unanimous -> langsung approved.
                        $image->label = $distinctLabels->first();
                        $image->label_status = 'approved';
                        $image->labeled_by = $votes->pluck('labeled_by')->implode(', ');
                        $image->prodi = $votes->pluck('prodi')->filter()->implode(', ');
                        $image->dispute_note = null;
                    } else {
                        // Ada disagreement -> antrean resolusi admin.
                        $image->label = null;
                        $image->label_status = 'dispute';
                        $image->labeled_by = $votes->pluck('labeled_by')->implode(', ');
                        $image->prodi = $votes->pluck('prodi')->filter()->implode(', ');
                        $image->dispute_note = null;
                    }
                } else {
                    // Masih mengumpulkan suara.
                    $image->label_status = 'collecting';
                    $image->labeled_by = $votes->pluck('labeled_by')->implode(', ');
                }

                $image->save();

                return [
                    'ok' => true,
                    'multi' => true,
                    'votes' => $votes->count(),
                    'required' => $required,
                    'status' => $image->label_status,
                ];
            }

            $image->update([
                'label' => $label,
                'labeled_by' => $nickname,
                'prodi' => $prodi,
                'label_status' => 'pending',
                'reserved_by' => null,
                'reserved_until' => null,
            ]);

            return ['ok' => true, 'multi' => false];
        });

        if (!$result['ok']) {
            if (($result['reason'] ?? '') === 'already_voted') {
                return response()->json(['error' => 'Kamu sudah memberi label untuk gambar ini.'], 409);
            }

            return response()->json(['error' => 'This image was already labeled by someone else! Fetching next image.'], 409);
        }

        if ($result['multi']) {
            $status = $result['status'];
            $message = 'Label tercatat (' . $result['votes'] . '/' . $result['required'] . ' suara).';

            if ($status === 'approved') {
                $message = 'Consensus tercapai — semua suara sama, label disetujui otomatis.';
            } elseif ($status === 'dispute') {
                $message = 'Label tercatat. Ada perbedaan pendapat, masuk antrean resolusi admin.';
            } else {
                $message .= ' Menunggu labeler lain.';
            }

            return response()->json([
                'success' => true,
                'multi_labeler' => true,
                'status' => $status,
                'votes' => $result['votes'],
                'required' => $result['required'],
                'message' => $message,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Fetch real-time leaderboard data.
     */
    public function getLeaderboard()
    {
        if (!$this->isWorkspaceAccessValid(request(), WorkspaceSetting::ACTIVE_LABELING)) {
            abort(403, 'Workspace labeling belum aktif atau passkey sudah tidak valid.');
        }

        $leaderboard = Image::select('labeled_by', DB::raw('count(*) as total'))
            ->whereIn('label_status', ['pending', 'approved'])
            ->whereNotNull('labeled_by')
            ->groupBy('labeled_by')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return response()->json($leaderboard);
    }

    /**
     * Admin view: login or dashboard.
     */
    public function adminView(Request $request)
    {
        if ($request->session()->has('admin_authenticated')) {
            $workspaceSetting = $this->getWorkspaceSetting();

            $competitionClasses = config('competition.classes', [
                ['id' => 0, 'name' => 'Aman', 'color' => 'emerald'],
                ['id' => 1, 'name' => 'Rusak', 'color' => 'blue'],
                ['id' => 2, 'name' => 'Lainnya', 'color' => 'amber'],
            ]);

            // Calculate stats
            $stats = [
                'total' => Image::count(),
                'unlabeled' => Image::where('label_status', 'unlabeled')->count(),
                'pending' => Image::where('label_status', 'pending')->count(),
                'approved' => Image::where('label_status', 'approved')->count(),
                'active_leases' => Image::where('label_status', 'unlabeled')->where('reserved_until', '>', now())->count(),
            ];

            // Butir 9: antrean resolusi dispute + metrik inter-annotator agreement.
            $multiLabelerMode = (bool) $workspaceSetting->multi_labeler_mode;
            $requiredLabelers = max(1, (int) config('competition.required_labelers', 2));

            $disputeStats = [
                'required_labelers' => $requiredLabelers,
                'collecting' => Image::where('label_status', 'collecting')->count(),
                'dispute' => Image::where('label_status', 'dispute')->count(),
                'agreed' => Image::where('label_status', 'approved')
                    ->whereHas('votes')
                    ->count(),
                'resolved_by_admin' => Image::whereNotNull('dispute_note')
                    ->where('label_status', 'approved')
                    ->count(),
                'total_votes' => ImageLabel::count(),
            ];

            $disputeItems = Image::where('label_status', 'dispute')
                ->with('votes')
                ->orderBy('updated_at', 'asc')
                ->limit(50)
                ->get();

            // Audit stats untuk partial preprocess (diambil dari AuditController@adminView pattern)
            $auditStats = [
                'total' => AuditCandidate::count(),
                'round1_pending' => AuditCandidate::whereNull('round1_decision')->count(),
                'round1_by_decision' => AuditCandidate::whereNotNull('round1_decision')
                    ->select('round1_decision', DB::raw('count(*) as total'))
                    ->groupBy('round1_decision')
                    ->pluck('total', 'round1_decision'),
                'round2_pending' => AuditCandidate::where('round1_decision', 'A')->whereNull('round2_decision')->count(),
                'round2_done' => AuditCandidate::where('round1_decision', 'A')->whereNotNull('round2_decision')->count(),
            ];

            // Dynamic per-class approved counts
            $classStats = [];
            foreach ($competitionClasses as $cls) {
                $count = Image::where('label', $cls['id'])->where('label_status', 'approved')->count();
                $stats[$cls['name']] = $count;
                $classStats[$cls['id']] = [
                    'name' => $cls['name'],
                    'color' => $cls['color'] ?? 'indigo',
                    'count' => $count,
                ];
            }

            // Active Leases breakdown (who is holding which images right now)
            $activeLeases = Image::select('reserved_by', DB::raw('COUNT(*) as total_held'), DB::raw('MIN(reserved_until) as earliest_expiry'), DB::raw('MAX(reserved_until) as latest_expiry'))
                ->where('label_status', 'unlabeled')
                ->whereNotNull('reserved_by')
                ->where('reserved_until', '>', now())
                ->groupBy('reserved_by')
                ->orderBy('total_held', 'desc')
                ->get();

            // Get unique labeler names that have pending items
            $pendingLabelers = Image::where('label_status', 'pending')
                ->whereNotNull('labeled_by')
                ->distinct()
                ->pluck('labeled_by')
                ->toArray();

            // Get unique labeler names that have approved items
            $approvedLabelers = Image::where('label_status', 'approved')
                ->whereNotNull('labeled_by')
                ->distinct()
                ->pluck('labeled_by')
                ->toArray();

            // PENDING QUERY (with Filter & Search)
            $filterUser = $request->query('filter_user');
            $searchPending = $request->query('search_pending');
            
            $pendingQuery = Image::where('label_status', 'pending');
            if ($filterUser) {
                $pendingQuery->where('labeled_by', $filterUser);
            }
            if ($searchPending) {
                // Remove potential .jpg extension to search by ID or filename
                $cleanSearchPending = str_ireplace(['.jpg', '.jpeg', '.png', '.webp'], '', $searchPending);
                $pendingQuery->where(function($q) use ($searchPending, $cleanSearchPending) {
                    $q->where('filename', 'like', '%' . $searchPending . '%')
                      ->orWhere('filename', 'like', '%' . $cleanSearchPending . '%');
                });
            }
            $pendingItems = $pendingQuery->orderBy('updated_at', 'asc')
                ->paginate(20, ['*'], 'pending_page');

            // APPROVED QUERY (with Filter & Search)
            $filterApprovedUser = $request->query('filter_approved_user');
            $searchApproved = $request->query('search_approved');

            $approvedQuery = Image::where('label_status', 'approved');
            if ($filterApprovedUser) {
                $approvedQuery->where('labeled_by', $filterApprovedUser);
            }
            if ($searchApproved) {
                $cleanSearchApproved = str_ireplace(['.jpg', '.jpeg', '.png', '.webp'], '', $searchApproved);
                $approvedQuery->where(function($q) use ($searchApproved, $cleanSearchApproved) {
                    $q->where('filename', 'like', '%' . $searchApproved . '%')
                      ->orWhere('filename', 'like', '%' . $cleanSearchApproved . '%');
                });
            }
            $approvedItems = $approvedQuery->orderBy('updated_at', 'desc')
                ->paginate(20, ['*'], 'approved_page');

            // Get database examples list for admin to delete/manage (stored on persistent volume)
            $manualExamples = [];
            foreach ($competitionClasses as $cls) {
                $manualExamples[$cls['id']] = [];
            }
            $dbExamples = \App\Models\ExampleImage::orderBy('created_at', 'desc')->get();
            foreach ($dbExamples as $dbEx) {
                if (isset($manualExamples[$dbEx->label])) {
                    $manualExamples[$dbEx->label][] = [
                        'id' => $dbEx->id,
                        'filename' => $dbEx->filename,
                        'url' => $dbEx->url
                    ];
                }
            }

            return view('admin', compact(
                'stats', 'auditStats', 'pendingItems', 'approvedItems', 'manualExamples',
                'pendingLabelers', 'filterUser', 'searchPending',
                'approvedLabelers', 'filterApprovedUser', 'searchApproved',
                'workspaceSetting', 'competitionClasses', 'classStats', 'activeLeases',
                'disputeStats', 'disputeItems', 'multiLabelerMode', 'requiredLabelers'
            ));
        }

        return view('admin_login');
    }

    /**
     * Admin login handling.
     */
    public function adminLogin(Request $request)
    {
        $password = $request->input('password');
        $correctPassword = env('ADMIN_PASSWORD', 'admin123');

        if ($password === $correctPassword) {
            $request->session()->put('admin_authenticated', true);
            return redirect()->route('admin');
        }

        return back()->withErrors(['password' => 'Password salah!']);
    }

    public function updateWorkspaceSettings(Request $request)
    {
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin');
        }

        $request->validate([
            'active_activity' => 'required|in:' . implode(',', WorkspaceSetting::ACTIVE_ACTIVITIES),
            'multi_labeler_mode' => 'nullable|boolean',
        ]);

        $setting = $this->getWorkspaceSetting();
        $setting->update([
            'active_activity' => $request->input('active_activity'),
            'multi_labeler_mode' => (bool) $request->input('multi_labeler_mode'),
        ]);

        return back()->with('success', 'Pengaturan workspace berhasil diperbarui.');
    }

    public function regenerateWorkspacePasskey(Request $request)
    {
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin');
        }

        $request->validate([
            'custom_passkey' => 'nullable|string|min:4|max:32|regex:/^[a-zA-Z0-9_\-]+$/',
        ]);

        $setting = $this->getWorkspaceSetting();
        $customPasskey = trim((string) $request->input('custom_passkey', ''));

        if ($customPasskey !== '') {
            $setting->update([
                'access_passkey' => strtoupper($customPasskey),
            ]);
            return back()->with('success', 'Passkey custom berhasil disimpan: ' . $setting->access_passkey);
        }

        $setting->update([
            'access_passkey' => strtoupper(Str::random(8)),
        ]);

        return back()->with('success', 'Passkey baru berhasil dibuat. Bagikan ke user yang berhak mengakses workspace.');
    }

    /**
     * Admin logout.
     */
    public function adminLogout(Request $request)
    {
        $request->session()->forget('admin_authenticated');
        return redirect()->route('admin');
    }

    /**
     * Internal helper to scan dataset path and register new images.
     */
    private function syncDatasetInternal()
    {
        $datasetPath = $this->getDatasetPath();

        if (!File::exists($datasetPath)) {
            throw new \Exception("Dataset folder not found at: {$datasetPath}. Please verify your .env file or upload a ZIP.");
        }

        // Scan folder for images (jpg, jpeg, png, gif, webp)
        $files = File::files($datasetPath);
        $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imageFiles = [];

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $supportedExtensions)) {
                $imageFiles[] = $file->getFilename();
            }
        }

        if (empty($imageFiles)) {
            return 0;
        }

        $insertedCount = 0;
        $existingFiles = Image::pluck('filename')->toArray();
        $existingFilesMap = array_flip($existingFiles);

        // Batch inserts for performance
        $chunks = array_chunk($imageFiles, 200);
        foreach ($chunks as $chunk) {
            $insertData = [];
            foreach ($chunk as $filename) {
                if (!isset($existingFilesMap[$filename])) {
                    $insertData[] = [
                        'filename' => $filename,
                        'label' => null,
                        'labeled_by' => null,
                        'label_status' => 'unlabeled',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (!empty($insertData)) {
                Image::insert($insertData);
                $insertedCount += count($insertData);
            }
        }

        return $insertedCount;
    }

    /**
     * Scan the local dataset directory and sync it with the database.
     */
    public function syncDataset()
    {
        try {
            $insertedCount = $this->syncDatasetInternal();
            return back()->with('success', "Dataset synced! Added {$insertedCount} new images. Total images: " . Image::count());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Handle ZIP dataset upload, unzipping, and auto-sync.
     */
    public function uploadDataset(Request $request)
    {
        set_time_limit(360);
        ini_set('memory_limit', '512M');

        $request->validate([
            'dataset_zip' => 'required|file|mimes:zip|max:204800' // Max 200MB ZIP
        ]);

        $file = $request->file('dataset_zip');
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        
        $tempZipPath = $tempDir . DIRECTORY_SEPARATOR . 'upload_' . time() . '.zip';
        $file->move($tempDir, basename($tempZipPath));

        $extractPath = $this->getDatasetPath();
        if (!File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($tempZipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            File::delete($tempZipPath);

            // Automatically run sync
            try {
                $insertedCount = $this->syncDatasetInternal();
                return back()->with('success', "Dataset ZIP berhasil diunggah & diekstrak! Menyinkronkan {$insertedCount} gambar baru ke database. Total gambar di database: " . Image::count());
            } catch (\Exception $e) {
                return back()->with('success', "Dataset ZIP berhasil diekstrak, namun gagal sinkron otomatis: " . $e->getMessage());
            }
        } else {
            File::delete($tempZipPath);
            return back()->with('error', "Gagal membuka atau mengekstrak file ZIP.");
        }
    }

    /**
     * Handle example images upload for visual guidelines.
     */
    public function uploadExamples(Request $request)
    {
        $request->validate([
            'label' => 'required|in:0,1,2',
            'example_images' => 'required|array',
            'example_images.*' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240' // Max 10MB per image
        ]);

        $label = (int) $request->input('label');
        $files = $request->file('example_images');
        $targetDir = public_path("dataset/examples/{$label}");

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $uploadedCount = 0;
        foreach ($files as $file) {
            $filename = 'example_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($targetDir, $filename);

            // Record in the database
            \App\Models\ExampleImage::create([
                'filename' => $filename,
                'label' => $label
            ]);

            $uploadedCount++;
        }

        return back()->with('success', "Berhasil mengunggah {$uploadedCount} gambar contoh baru untuk label {$label}!");
    }

    /**
     * Handle manual example image deletion.
     */
    public function deleteExample(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $example = \App\Models\ExampleImage::findOrFail($request->input('id'));
        $filePath = public_path("dataset/examples/{$example->label}/{$example->filename}");

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $example->delete();

        return back()->with('success', "Gambar contoh berhasil dihapus.");
    }

    /**
     * Butir 9: admin menyelesaikan gambar yang statusnya 'dispute' dengan
     * memilih label final secara manual.
     */
    public function resolveDispute(Request $request, $id)
    {
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin');
        }

        $validClassIds = array_column(config('competition.classes', []), 'id');
        if (empty($validClassIds)) {
            $validClassIds = [0, 1, 2];
        }

        $request->validate([
            'label' => ['required', \Illuminate\Validation\Rule::in($validClassIds)],
            'dispute_note' => 'nullable|string|max:255',
        ]);

        $image = Image::findOrFail($id);
        $image->update([
            'label' => $request->input('label'),
            'label_status' => 'approved',
            'dispute_note' => $request->input('dispute_note'),
            'reserved_by' => null,
            'reserved_until' => null,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Dispute {$image->filename} resolved dan disetujui.");
    }

    /**
     * Butir 9: admin menolak consensus -> kembalikan seluruh suara ke antrean
     * sehingga gambar bisa dilabeli ulang oleh labeler yang berbeda.
     */
    public function reopenDispute(Request $request, $id)
    {
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin');
        }

        $image = Image::findOrFail($id);

        ImageLabel::where('image_id', $image->id)->delete();

        $image->update([
            'label' => null,
            'labeled_by' => null,
            'prodi' => null,
            'label_status' => 'unlabeled',
            'dispute_note' => null,
            'reserved_by' => null,
            'reserved_until' => null,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Dispute {$image->filename} dikembalikan ke antrean publik.");
    }

    /**
     * Action to approve a pending label.
     */
    public function approveLabel($id)
    {
        $image = Image::findOrFail($id);
        $image->update(['label_status' => 'approved']);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Label approved for image {$image->filename}!");
    }

    /**
     * Action to reject a pending label.
     */
    public function rejectLabel($id)
    {
        $image = Image::findOrFail($id);
        $image->update([
            'label' => null,
            'labeled_by' => null,
            'label_status' => 'unlabeled'
        ]);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Label rejected for image {$image->filename}. It is now back in the unlabeled pool.");
    }

    /**
     * Action to edit/update a label and approve it.
     */
    public function updateLabel(Request $request, $id)
    {
        $request->validate([
            'label' => 'required|in:0,1,2'
        ]);

        $image = Image::findOrFail($id);
        $image->update([
            'label' => $request->input('label'),
            'label_status' => 'approved' // Automatically approve when edited by admin
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Label updated and approved for image {$image->filename}!");
    }

    /**
     * Export labeled dataset to CSV.
     */
    public function downloadCsv()
    {
        // Get all approved labels
        $approvedImages = Image::where('label_status', 'approved')
            ->orderBy('filename', 'asc')
            ->get();

        $csvFileName = 'labeled_dataset_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=" . $csvFileName,
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($approvedImages) {
            $file = fopen('php://output', 'w');
            
            // CSV columns: id, label
            fputcsv($file, ['id', 'label']);

            foreach ($approvedImages as $image) {
                // Extract filename without extension (e.g., "1.jpg" becomes "1")
                $id = pathinfo($image->filename, PATHINFO_FILENAME);
                fputcsv($file, [$id, $image->label]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Admin action: Release leases held by a specific user.
     */
    public function adminReleaseUserLease(Request $request)
    {
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin');
        }

        $targetUser = $request->input('nickname');
        if ($targetUser) {
            $count = Image::where('label_status', 'unlabeled')
                ->where('reserved_by', $targetUser)
                ->update([
                    'reserved_by' => null,
                    'reserved_until' => null,
                ]);

            return back()->with('success', "Berhasil melepaskan {$count} gambar yang tertahan oleh {$targetUser}.");
        }

        return back()->with('error', 'Nama pengguna tidak valid.');
    }

    /**
     * Admin action: Release all active leases across the entire dataset.
     */
    public function adminReleaseAllLeases(Request $request)
    {
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin');
        }

        $count = Image::where('label_status', 'unlabeled')
            ->whereNotNull('reserved_by')
            ->update([
                'reserved_by' => null,
                'reserved_until' => null,
            ]);

        return back()->with('success', "Berhasil mereset seluruh lease aktif ({$count} gambar dibebaskan ke antrean publik).");
    }

    /**
     * Export complete dataset with annotator details, prodi, and timestamps.
     */
    public function downloadFullCsv()
    {
        $approvedImages = Image::where('label_status', 'approved')
            ->orderBy('filename', 'asc')
            ->get();

        $classes = config('competition.classes', []);
        $classMap = [];
        foreach ($classes as $cls) {
            $classMap[$cls['id']] = $cls['name'];
        }

        $csvFileName = 'full_dataset_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=" . $csvFileName,
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($approvedImages, $classMap) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['id', 'filename', 'label_id', 'label_name', 'labeled_by', 'prodi', 'approved_at']);

            foreach ($approvedImages as $image) {
                $id = pathinfo($image->filename, PATHINFO_FILENAME);
                $labelName = $classMap[$image->label] ?? ('Class ' . $image->label);
                fputcsv($file, [
                    $id,
                    $image->filename,
                    $image->label,
                    $labelName,
                    $image->labeled_by,
                    $image->prodi,
                    $image->updated_at ? $image->updated_at->toDateTimeString() : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export stratified train/val split (80:20) in a single ZIP containing train.csv and val.csv.
     */
    public function downloadTrainValSplit()
    {
        $approvedImages = Image::where('label_status', 'approved')->get();

        if ($approvedImages->isEmpty()) {
            return back()->with('error', 'Belum ada data terlabel yang disetujui untuk di-export.');
        }

        // Group by label for stratified sampling
        $grouped = $approvedImages->groupBy('label');
        $trainRecords = [];
        $valRecords = [];

        foreach ($grouped as $label => $items) {
            $shuffled = $items->shuffle();
            $total = $shuffled->count();
            $trainCount = (int) round($total * 0.8);

            $trainSlice = $shuffled->slice(0, $trainCount);
            $valSlice = $shuffled->slice($trainCount);

            foreach ($trainSlice as $item) {
                $trainRecords[] = [pathinfo($item->filename, PATHINFO_FILENAME), $item->label];
            }
            foreach ($valSlice as $item) {
                $valRecords[] = [pathinfo($item->filename, PATHINFO_FILENAME), $item->label];
            }
        }

        $zipFileName = 'train_val_split_80_20_' . date('Y-m-d_H-i-s') . '.zip';
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Write train.csv
            $trainHandle = fopen('php://memory', 'r+');
            fputcsv($trainHandle, ['id', 'label']);
            foreach ($trainRecords as $row) {
                fputcsv($trainHandle, $row);
            }
            rewind($trainHandle);
            $zip->addFromString('train.csv', stream_get_contents($trainHandle));
            fclose($trainHandle);

            // Write val.csv
            $valHandle = fopen('php://memory', 'r+');
            fputcsv($valHandle, ['id', 'label']);
            foreach ($valRecords as $row) {
                fputcsv($valHandle, $row);
            }
            rewind($valHandle);
            $zip->addFromString('val.csv', stream_get_contents($valHandle));
            fclose($valHandle);

            $zip->close();

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Gagal membuat file ZIP split dataset.');
    }

    /**
     * Approve all pending labels globally or filtered by user/search.
     */
    public function approveAll(Request $request)
    {
        $labeledBy = $request->input('labeled_by');
        $search = $request->input('search');

        $query = Image::where('label_status', 'pending');

        if ($labeledBy) {
            $query->where('labeled_by', $labeledBy);
        }
        if ($search) {
            $cleanSearch = str_ireplace(['.jpg', '.jpeg', '.png', '.webp'], '', $search);
            $query->where(function($q) use ($search, $cleanSearch) {
                $q->where('filename', 'like', '%' . $search . '%')
                  ->orWhere('filename', 'like', '%' . $cleanSearch . '%');
            });
        }

        $approvedCount = $query->update(['label_status' => 'approved']);

        $message = $labeledBy 
            ? "Berhasil menyetujui {$approvedCount} label dari {$labeledBy} secara massal!"
            : "Berhasil menyetujui {$approvedCount} seluruh label pending secara massal!";

        return back()->with('success', $message);
    }

    /**
     * Reject all approved labels (mass reverse to unlabeled pool) globally or filtered by user/search.
     */
    public function rejectAll(Request $request)
    {
        $labeledBy = $request->input('labeled_by');
        $search = $request->input('search');

        $query = Image::where('label_status', 'approved');

        if ($labeledBy) {
            $query->where('labeled_by', $labeledBy);
        }
        if ($search) {
            $cleanSearch = str_ireplace(['.jpg', '.jpeg', '.png', '.webp'], '', $search);
            $query->where(function($q) use ($search, $cleanSearch) {
                $q->where('filename', 'like', '%' . $search . '%')
                  ->orWhere('filename', 'like', '%' . $cleanSearch . '%');
            });
        }

        $rejectedCount = $query->update([
            'label' => null,
            'labeled_by' => null,
            'prodi' => null,
            'label_status' => 'unlabeled'
        ]);

        $message = $labeledBy 
            ? "Berhasil membatalkan persetujuan {$rejectedCount} label dari {$labeledBy} secara massal!"
            : "Berhasil membatalkan persetujuan {$rejectedCount} seluruh label secara massal!";

        return back()->with('success', $message);
    }

    /**
     * Reject all pending labels (mass reverse to unlabeled pool) globally or filtered by user/search.
     */
    public function rejectAllPending(Request $request)
    {
        $labeledBy = $request->input('labeled_by');
        $search = $request->input('search');

        $query = Image::where('label_status', 'pending');

        if ($labeledBy) {
            $query->where('labeled_by', $labeledBy);
        }
        if ($search) {
            $cleanSearch = str_ireplace(['.jpg', '.jpeg', '.png', '.webp'], '', $search);
            $query->where(function($q) use ($search, $cleanSearch) {
                $q->where('filename', 'like', '%' . $search . '%')
                  ->orWhere('filename', 'like', '%' . $cleanSearch . '%');
            });
        }

        $rejectedCount = $query->update([
            'label' => null,
            'labeled_by' => null,
            'prodi' => null,
            'label_status' => 'unlabeled'
        ]);

        $message = $labeledBy 
            ? "Berhasil menolak {$rejectedCount} usulan label dari {$labeledBy} secara massal!"
            : "Berhasil menolak {$rejectedCount} usulan seluruh label pending secara massal!";

        return back()->with('success', $message);
    }
}
