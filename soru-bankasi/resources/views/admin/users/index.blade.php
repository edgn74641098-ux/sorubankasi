@extends('layouts.admin', ['pageTitle' => 'Kullanicilar', 'title' => 'Kullanicilar'])

@section('content')
    <section class="admin-command-hero">
        <div>
            <p class="eyebrow">Erisim ve Performans</p>
            <h1>Kullanici yonetimi</h1>
            <p>Kullanicilari rol, dogrulama ve aktivite durumuna gore izleyin; test performansini ve katkilarini tek listede yonetin.</p>
        </div>
        <div class="admin-command-score">
            <span>Toplam Kullanici</span>
            <strong>{{ number_format($stats['total']) }}</strong>
            <small>{{ number_format($stats['active_7']) }} kullanici son 7 gunde aktif</small>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="admin-panel admin-user-stats mb-4" aria-label="Kullanici ozetleri">
        <div class="admin-panel__content">
            <div class="admin-user-stat-grid">
                <div class="admin-user-stat">
                    <i class="bi bi-person-plus"></i>
                    <div>
                        <span>Bugun Kayit</span>
                        <strong>{{ number_format($stats['today']) }}</strong>
                        <small>Yeni hesaplar</small>
                    </div>
                </div>
                <div class="admin-user-stat">
                    <i class="bi bi-person-check"></i>
                    <div>
                        <span>Aktif / Pasif</span>
                        <strong>{{ number_format($stats['active']) }} / {{ number_format($stats['passive']) }}</strong>
                        <small>Hesap durumu</small>
                    </div>
                </div>
                <div class="admin-user-stat">
                    <i class="bi bi-envelope-exclamation"></i>
                    <div>
                        <span>Dogrulanmamis</span>
                        <strong>{{ number_format($stats['unverified']) }}</strong>
                        <small>E-posta bekliyor</small>
                    </div>
                </div>
                <div class="admin-user-stat">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <span>Admin</span>
                        <strong>{{ number_format($stats['admins']) }}</strong>
                        <small>Yuksek yetki</small>
                    </div>
                </div>
                <div class="admin-user-stat">
                    <i class="bi bi-pencil-square"></i>
                    <div>
                        <span>Editor / User</span>
                        <strong>{{ number_format($stats['editors']) }} / {{ number_format($stats['regular_users']) }}</strong>
                        <small>Operasyon rolleri</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h2>Kullanici Listesi</h2>
                <p>Rol, dogrulama ve aktivite filtreleriyle hesaplari net sekilde yonetin.</p>
            </div>
        </div>

        <div class="admin-panel__content">
            <form method="GET" action="{{ route('admin.users.index') }}" class="admin-user-filter mb-4">
                <div>
                    <label for="search" class="form-label">Ara</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="Ad veya e-posta">
                </div>
                <div>
                    <label for="role_id" class="form-label">Rol</label>
                    <select name="role_id" id="role_id" class="form-select form-select-sm">
                        <option value="">Tum roller</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $filters['role_id'] === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="email_status" class="form-label">E-posta</label>
                    <select name="email_status" id="email_status" class="form-select form-select-sm">
                        <option value="">Tumu</option>
                        <option value="verified" @selected($filters['email_status'] === 'verified')>Dogrulanmis</option>
                        <option value="unverified" @selected($filters['email_status'] === 'unverified')>Dogrulanmamis</option>
                    </select>
                </div>
                <div>
                    <label for="account_status" class="form-label">Hesap</label>
                    <select name="account_status" id="account_status" class="form-select form-select-sm">
                        <option value="">Tumu</option>
                        <option value="active" @selected($filters['account_status'] === 'active')>Aktif</option>
                        <option value="passive" @selected($filters['account_status'] === 'passive')>Pasif</option>
                    </select>
                </div>
                <div>
                    <label for="activity" class="form-label">Aktivite</label>
                    <select name="activity" id="activity" class="form-select form-select-sm">
                        <option value="">Tumu</option>
                        <option value="active_7" @selected($filters['activity'] === 'active_7')>Son 7 gun aktif</option>
                        <option value="inactive_30" @selected($filters['activity'] === 'inactive_30')>30 gun pasif</option>
                        <option value="no_tests" @selected($filters['activity'] === 'no_tests')>Test cozmeyen</option>
                    </select>
                </div>
                <div class="admin-user-filter__actions">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-filter me-1"></i> Filtrele
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Temizle</a>
                </div>
            </form>

            <div class="admin-user-list">
                @forelse($users as $user)
                    @php
                        $questionTotal = (int) ($user->question_total ?? 0);
                        $correctTotal = (int) ($user->correct_total ?? 0);
                        $wrongTotal = (int) ($user->wrong_total ?? 0);
                        $accuracy = $questionTotal > 0 ? round(($correctTotal / $questionTotal) * 100, 1) : null;
                        $lastTestAt = $user->last_test_at ? \Illuminate\Support\Carbon::parse($user->last_test_at) : null;
                        $lastLoginAt = $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at) : null;
                        $profileModalId = 'userDetail'.$user->id;
                        $reportsModalId = 'userReports'.$user->id;
                        $submissionsModalId = 'userSubmissions'.$user->id;
                        $statusLabels = [
                            'pending' => 'Beklemede',
                            'approved' => 'Onaylandi',
                            'rejected' => 'Reddedildi',
                            'resolved' => 'Cozuldu',
                            'admin_review' => 'Admin incelemede',
                        ];
                        $categoryLabels = [
                            'WRONG_ANSWER' => 'Yanlis cevap',
                            'UNCLEAR_WORDING' => 'Ifade belirsiz',
                            'TYPO' => 'Yazim hatasi',
                            'OTHER' => 'Diger',
                        ];
                    @endphp

                    <article class="admin-user-card">
                        <div class="admin-user-card__main">
                            <button type="button" class="admin-user-identity admin-user-identity-button" data-bs-toggle="modal" data-bs-target="#{{ $profileModalId }}">
                                <div class="admin-user-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</div>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                                    <div class="text-muted small text-truncate">{{ $user->email }}</div>
                                    <div class="text-muted small">Kayit: {{ $user->created_at->format('d.m.Y') }}</div>
                                </div>
                            </button>

                            <div class="admin-user-list-meta">
                                <span>Rol / Durum</span>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge text-bg-{{ $user->isAdmin() ? 'danger' : ($user->isEditor() ? 'warning' : 'secondary') }}">
                                        {{ $user->role?->name ?? '-' }}
                                    </span>
                                    <span class="badge text-bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Pasif' }}
                                    </span>
                                    <span class="badge text-bg-{{ $user->email_verified_at ? 'success' : 'light text-dark border' }}">
                                        {{ $user->email_verified_at ? 'Dogrulanmis' : 'Dogrulanmamis' }}
                                    </span>
                                </div>
                            </div>

                            <div class="admin-user-list-meta">
                                <span>Puan</span>
                                <strong>{{ number_format((int) $user->total_score) }}</strong>
                                <small>{{ number_format($user->finished_tests_count) }} test &middot; {{ number_format($questionTotal) }} soru</small>
                            </div>

                            <div class="admin-user-list-meta">
                                <span>Test Performansi</span>
                                <strong>{{ $accuracy !== null ? '%' . number_format($accuracy, 1) : '-' }}</strong>
                                <small>{{ number_format($correctTotal) }} dogru &middot; {{ number_format($wrongTotal) }} yanlis</small>
                            </div>

                            <div class="admin-user-list-meta">
                                <span>Katki</span>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="badge text-bg-info admin-user-link-badge" data-bs-toggle="modal" data-bs-target="#{{ $submissionsModalId }}">
                                        {{ number_format($user->submitted_questions_count) }} oneri
                                    </button>
                                    <button type="button" class="badge text-bg-light text-dark border admin-user-link-badge" data-bs-toggle="modal" data-bs-target="#{{ $reportsModalId }}">
                                        {{ number_format($user->question_reports_count) }} itiraz
                                    </button>
                                </div>
                            </div>

                            <div class="admin-user-list-meta">
                                <span>Son Aktivite</span>
                                @if($lastTestAt)
                                    <strong>{{ $lastTestAt->format('d.m.Y H:i') }}</strong>
                                    <small>{{ $lastTestAt->diffForHumans() }}</small>
                                @else
                                    <strong>-</strong>
                                    <small>Test aktivitesi yok</small>
                                @endif
                                <small>Giris: {{ $lastLoginAt ? $lastLoginAt->format('d.m.Y H:i') : '-' }}</small>
                            </div>
                        </div>

                        <div class="admin-user-actionbar">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#{{ $profileModalId }}">
                                <i class="bi bi-eye me-1"></i> Detay
                            </button>

                            <form method="POST" action="{{ route('admin.users.update-status', $user) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    {{ $user->is_active ? 'Pasif yap' : 'Aktif yap' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="admin-user-role-form">
                                @csrf
                                @method('PATCH')
                                <select name="role_id" class="form-select form-select-sm" aria-label="{{ $user->name }} rol secimi">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Kaydet</button>
                            </form>

                            @if(! $user->isAdmin())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Bu kullaniciyi silmek istediginize emin misiniz? Kullanici hesabi pasif olarak listeden kaldirilir, gecmis kayitlar korunur.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                                </form>
                            @endif
                        </div>
                    </article>

                    <div class="modal fade text-start" id="{{ $profileModalId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h2 class="modal-title h5">{{ $user->name }}</h2>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <div class="border rounded p-3 h-100">
                                                <div class="text-muted small">Rol</div>
                                                <div class="fw-bold">{{ $user->role?->name ?? '-' }}</div>
                                                <div class="small mt-1 {{ $user->is_active ? 'text-success' : 'text-danger' }}">
                                                    {{ $user->is_active ? 'Aktif hesap' : 'Pasif hesap' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3 h-100">
                                                <div class="text-muted small">Puan</div>
                                                <div class="fw-bold">{{ number_format((int) $user->total_score) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3 h-100">
                                                <div class="text-muted small">Basari</div>
                                                <div class="fw-bold">{{ $accuracy !== null ? '%' . number_format($accuracy, 1) : '-' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3 h-100">
                                                <div class="text-muted small">Son Giris IP</div>
                                                <div class="fw-bold">{{ $user->last_login_ip ?: '-' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-4">
                                            <div class="border rounded p-3 h-100">
                                                <h3 class="h6 fw-bold mb-3">Test Performansi</h3>
                                                <div class="small text-muted">Bitmis test: <strong class="text-dark">{{ number_format($user->finished_tests_count) }}</strong></div>
                                                <div class="small text-muted">Cozulen soru: <strong class="text-dark">{{ number_format($questionTotal) }}</strong></div>
                                                <div class="small text-muted">Dogru / Yanlis: <strong class="text-dark">{{ number_format($correctTotal) }} / {{ number_format($wrongTotal) }}</strong></div>
                                                <div class="small text-muted">Son test: <strong class="text-dark">{{ $lastTestAt ? $lastTestAt->format('d.m.Y H:i') : '-' }}</strong></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="border rounded p-3 h-100">
                                                <h3 class="h6 fw-bold mb-3">Katki Kalitesi</h3>
                                                <div class="small text-muted">Oneri: <strong class="text-dark">{{ number_format($user->submitted_questions_count) }}</strong></div>
                                                <div class="small text-muted">Kabul / Red: <strong class="text-dark">{{ number_format($user->approved_submissions_count) }} / {{ number_format($user->rejected_submissions_count) }}</strong></div>
                                                <div class="small text-muted">Itiraz: <strong class="text-dark">{{ number_format($user->question_reports_count) }}</strong></div>
                                                <div class="small text-muted">Kabul / Red: <strong class="text-dark">{{ number_format($user->approved_reports_count) }} / {{ number_format($user->rejected_reports_count) }}</strong></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="border rounded p-3 h-100">
                                                <h3 class="h6 fw-bold mb-3">Hesap Bilgisi</h3>
                                                <div class="small text-muted">Kayit: <strong class="text-dark">{{ $user->created_at->format('d.m.Y H:i') }}</strong></div>
                                                <div class="small text-muted">E-posta: <strong class="text-dark">{{ $user->email_verified_at ? 'Dogrulanmis' : 'Dogrulanmamis' }}</strong></div>
                                                <div class="small text-muted">Son giris: <strong class="text-dark">{{ $lastLoginAt ? $lastLoginAt->format('d.m.Y H:i') : '-' }}</strong></div>
                                                <div class="small text-muted">IP: <strong class="text-dark">{{ $user->last_login_ip ?: '-' }}</strong></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border rounded p-3 mt-3">
                                        <h3 class="h6 fw-bold mb-3">Son 5 Giris IP</h3>
                                        @if($user->recent_login_ips->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                @foreach($user->recent_login_ips as $loginLog)
                                                    <span class="badge text-bg-light text-dark border">
                                                        {{ $loginLog->ip_address }}
                                                        <span class="text-muted ms-1">{{ $loginLog->created_at->format('d.m H:i') }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted small mb-3">Giris IP kaydi bulunmuyor.</div>
                                        @endif

                                        <h3 class="h6 fw-bold mb-3">Son Audit Islemleri</h3>
                                        @forelse($user->recent_audit_logs as $log)
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 border-bottom py-2">
                                                <div>
                                                    <div class="fw-semibold">{{ $log->action }}</div>
                                                    <div class="text-muted small">{{ $log->reason ?: '-' }}</div>
                                                </div>
                                                <div class="text-muted small text-md-end">
                                                    {{ $log->created_at->format('d.m.Y H:i') }}<br>
                                                    {{ $log->ip_address ?: '-' }}
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">Audit kaydi bulunmuyor.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kapat</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade text-start" id="{{ $reportsModalId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h2 class="modal-title h5">{{ $user->name }} - Itirazlari</h2>
                                        <div class="text-muted small">{{ number_format($user->admin_question_reports->count()) }} kayit listeleniyor</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    @forelse($user->admin_question_reports as $report)
                                        @php
                                            $question = $report->question;
                                            $reportStatus = $statusLabels[$report->status] ?? $report->status;
                                            $reportStatusClass = match ($report->status) {
                                                'approved', 'resolved' => 'success',
                                                'rejected' => 'danger',
                                                'pending' => 'warning',
                                                default => 'secondary',
                                            };
                                            $reportOptions = $question ? [
                                                'A' => $question->option_a,
                                                'B' => $question->option_b,
                                                'C' => $question->option_c,
                                                'D' => $question->option_d,
                                                'E' => $question->option_e,
                                            ] : [];
                                        @endphp
                                        <article class="admin-user-detail-row">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                                                <div>
                                                    <div class="fw-bold">Itiraz #{{ $report->id }}</div>
                                                    <div class="text-muted small">
                                                        {{ $question?->subject?->name ?? 'Ders bulunamadi' }} · {{ $report->created_at->format('d.m.Y H:i') }}
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge text-bg-{{ $reportStatusClass }}">{{ $reportStatus }}</span>
                                                    <span class="badge text-bg-light text-dark border">{{ $categoryLabels[$report->category] ?? $report->category }}</span>
                                                </div>
                                            </div>

                                            <div class="admin-user-detail-block mb-3">
                                                <span>Soru</span>
                                                <p>{{ $question?->question_text ?? 'Soru kaydi bulunamadi.' }}</p>
                                            </div>

                                            @if($question)
                                                <div class="row g-2 mb-3">
                                                    @foreach($reportOptions as $option => $text)
                                                        <div class="col-md-6 col-xl">
                                                            <div class="admin-user-option {{ $question->correct_option === $option ? 'is-correct' : '' }} {{ $report->suggested_correct_option === $option ? 'is-suggested' : '' }}">
                                                                <strong>{{ $option }}</strong>
                                                                <span>{{ $text }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="row g-3">
                                                <div class="col-lg-4">
                                                    <div class="admin-user-detail-block h-100">
                                                        <span>Onerilen / mevcut cevap</span>
                                                        <p>Oneri: <strong>{{ $report->suggested_correct_option ?? '-' }}</strong></p>
                                                        <p>Mevcut dogru: <strong>{{ $question?->correct_option ?? '-' }}</strong></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="admin-user-detail-block h-100">
                                                        <span>Kullanici notu</span>
                                                        <p>{{ $report->note ?: '-' }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="admin-user-detail-block h-100">
                                                        <span>Inceleme</span>
                                                        <p>Inceleyen: <strong>{{ $report->reviewedBy?->name ?? '-' }}</strong></p>
                                                        <p>Tarih: <strong>{{ $report->reviewed_at?->format('d.m.Y H:i') ?? '-' }}</strong></p>
                                                        <p>Not: {{ $report->review_note ?: '-' }}</p>
                                                        <p>Mesaj: {{ $report->user_message ?: '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="admin-empty-state">Bu kullanicinin itiraz kaydi bulunmuyor.</div>
                                    @endforelse
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kapat</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade text-start" id="{{ $submissionsModalId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h2 class="modal-title h5">{{ $user->name }} - Onerileri</h2>
                                        <div class="text-muted small">{{ number_format($user->admin_submitted_questions->count()) }} kayit listeleniyor</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    @forelse($user->admin_submitted_questions as $submission)
                                        @php
                                            $payload = $submission->payload_json ?? [];
                                            $submissionStatus = $statusLabels[$submission->status] ?? $submission->status;
                                            $submissionStatusClass = match ($submission->status) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'pending', 'admin_review' => 'warning',
                                                default => 'secondary',
                                            };
                                            $submittedOptions = $payload['options'] ?? [];
                                        @endphp
                                        <article class="admin-user-detail-row">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                                                <div>
                                                    <div class="fw-bold">Oneri #{{ $submission->id }}</div>
                                                    <div class="text-muted small">
                                                        {{ $submission->subject?->name ?? 'Ders bulunamadi' }} · {{ $submission->created_at->format('d.m.Y H:i') }}
                                                    </div>
                                                </div>
                                                <span class="badge text-bg-{{ $submissionStatusClass }}">{{ $submissionStatus }}</span>
                                            </div>

                                            <div class="admin-user-detail-block mb-3">
                                                <span>Soru metni</span>
                                                <p>{{ $payload['question_text'] ?? '-' }}</p>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                                    <div class="col-md-6 col-xl">
                                                        <div class="admin-user-option {{ ($payload['correct_option'] ?? null) === $option ? 'is-correct' : '' }}">
                                                            <strong>{{ $option }}</strong>
                                                            <span>{{ $submittedOptions[$option] ?? '-' }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-lg-4">
                                                    <div class="admin-user-detail-block h-100">
                                                        <span>Onerilen dogru cevap</span>
                                                        <p><strong>{{ $payload['correct_option'] ?? '-' }}</strong></p>
                                                        <p>Olusan soru: <strong>{{ $submission->approvedQuestion ? '#'.$submission->approvedQuestion->id : '-' }}</strong></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="admin-user-detail-block h-100">
                                                        <span>Aciklama</span>
                                                        <p>{{ $payload['explanation_text'] ?? '-' }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="admin-user-detail-block h-100">
                                                        <span>Inceleme</span>
                                                        <p>Inceleyen: <strong>{{ $submission->reviewedBy?->name ?? '-' }}</strong></p>
                                                        <p>Tarih: <strong>{{ $submission->reviewed_at?->format('d.m.Y H:i') ?? '-' }}</strong></p>
                                                        <p>Not: {{ $submission->review_note ?: '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="admin-empty-state">Bu kullanicinin soru onerisi bulunmuyor.</div>
                                    @endforelse
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kapat</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="admin-empty-state">Filtrelere uygun kullanici bulunamadi.</div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </section>
@endsection
