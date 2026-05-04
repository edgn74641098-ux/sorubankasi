@extends('layouts.admin', ['pageTitle' => 'Audit Log', 'title' => 'Audit Log'])

@section('content')
    <section class="admin-command-hero">
        <div>
            <p class="eyebrow">Guvenlik ve Operasyon Izleme</p>
            <h1>Audit log</h1>
            <p>Kritik kullanici, soru, ders, ayar, giris ve arsiv islemlerini filtreleyin; IP, aktor ve degisim detaylarini inceleyin.</p>
        </div>
        <div class="admin-command-score">
            <span>Bugunku Kayit</span>
            <strong>{{ number_format($stats['today']) }}</strong>
            <small>{{ number_format($stats['critical_today']) }} kritik islem</small>
        </div>
    </section>

    <section class="admin-user-stats mb-4" aria-label="Audit log ozetleri">
        <div class="admin-user-stat-grid">
            <div class="admin-user-stat">
                <i class="bi bi-shield-exclamation"></i>
                <div>
                    <span>Kritik Bugun</span>
                    <strong>{{ number_format($stats['critical_today']) }}</strong>
                    <small>Ayar, rol, arşiv ve geri alma</small>
                </div>
            </div>
            <div class="admin-user-stat">
                <i class="bi bi-person-x"></i>
                <div>
                    <span>Giris Riski</span>
                    <strong>{{ number_format($stats['failed_logins_today']) }}</strong>
                    <small>Basarisiz veya engellenen giris</small>
                </div>
            </div>
            <div class="admin-user-stat">
                <i class="bi bi-sliders"></i>
                <div>
                    <span>Ayar Degisimi</span>
                    <strong>{{ number_format($stats['settings_changes_week']) }}</strong>
                    <small>Son 7 gun</small>
                </div>
            </div>
            <div class="admin-user-stat">
                <i class="bi bi-list-check"></i>
                <div>
                    <span>Toplam Filtre</span>
                    <strong>{{ number_format($logs->total()) }}</strong>
                    <small>Mevcut filtre sonucu</small>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h2>Kayitlar</h2>
                <p>Olay turu, kullanici, kaynak, IP ve tarih araligi ile arama yapin.</p>
            </div>
        </div>

        <div class="admin-panel__content">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="admin-user-filter mb-4">
                <div>
                    <label for="severity" class="form-label">Kategori</label>
                    <select name="severity" id="severity" class="form-select form-select-sm">
                        <option value="">Tum kategoriler</option>
                        <option value="critical" @selected($filters['severity'] === 'critical')>Kritik</option>
                        <option value="security" @selected($filters['severity'] === 'security')>Guvenlik</option>
                        <option value="content" @selected($filters['severity'] === 'content')>Icerik</option>
                    </select>
                </div>
                <div>
                    <label for="action" class="form-label">Aksiyon</label>
                    <input list="audit-actions" type="text" name="action" id="action" class="form-control form-control-sm" value="{{ $filters['action'] }}">
                    <datalist id="audit-actions">
                        @foreach($actions as $action)
                            <option value="{{ $action }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label for="entity_type" class="form-label">Kaynak</label>
                    <select name="entity_type" id="entity_type" class="form-select form-select-sm">
                        <option value="">Tum kaynaklar</option>
                        @foreach($entityTypes as $entityType)
                            <option value="{{ $entityType }}" @selected($filters['entity_type'] === $entityType)>{{ $entityType }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="actor_id" class="form-label">Kullanici</label>
                    <select name="actor_id" id="actor_id" class="form-select form-select-sm">
                        <option value="">Tum kullanicilar</option>
                        @foreach($actors as $actor)
                            <option value="{{ $actor->id }}" @selected((string) $filters['actor_id'] === (string) $actor->id)>{{ $actor->name }} - {{ $actor->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ip_address" class="form-label">IP</label>
                    <input type="text" name="ip_address" id="ip_address" class="form-control form-control-sm" value="{{ $filters['ip_address'] }}">
                </div>
                <div>
                    <label for="date_from" class="form-label">Baslangic</label>
                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
                </div>
                <div>
                    <label for="date_to" class="form-label">Bitis</label>
                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
                </div>
                <div class="admin-user-filter__actions">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filtrele</button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">Temizle</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle admin-table admin-audit-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kullanici</th>
                            <th>Aksiyon</th>
                            <th>Kaynak</th>
                            <th>IP</th>
                            <th>Gerekce</th>
                            <th class="text-end">Detay</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $tone = str_starts_with($log->action, 'auth.') ? 'primary' : (str_contains($log->action, 'deleted') || str_contains($log->action, 'archived') ? 'danger' : (str_contains($log->action, 'updated') || str_contains($log->action, 'settings') ? 'warning' : 'secondary'));
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    <div class="fw-semibold">{{ $log->created_at->format('d.m H:i') }}</div>
                                    <div class="text-muted small">{{ $log->created_at->format('Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-truncate sb-max-160">{{ $log->actor?->name ?? 'Sistem' }}</div>
                                    <div class="text-muted small text-truncate sb-max-160">{{ $log->actor?->email ?? '-' }}</div>
                                </td>
                                <td><span class="badge text-bg-{{ $tone }}">{{ $log->action }}</span></td>
                                <td class="text-nowrap">{{ $log->entity_type }} @if($log->entity_id)#{{ $log->entity_id }}@endif</td>
                                <td class="text-nowrap">{{ $log->ip_address ?: '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($log->reason ?: '-', 70) }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#auditLog{{ $log->id }}">
                                        Detay
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">Kayit bulunamadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @foreach($logs as $log)
                <div class="modal fade" id="auditLog{{ $log->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title h5">{{ $log->action }}</h2>
                                    <div class="text-muted small">{{ $log->created_at->format('d.m.Y H:i:s') }} · {{ $log->entity_type }} @if($log->entity_id)#{{ $log->entity_id }}@endif</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="admin-user-detail-block h-100">
                                            <span>Kullanici</span>
                                            <p><strong>{{ $log->actor?->name ?? 'Sistem' }}</strong></p>
                                            <p>{{ $log->actor?->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="admin-user-detail-block h-100">
                                            <span>Baglanti</span>
                                            <p>IP: <strong>{{ $log->ip_address ?: '-' }}</strong></p>
                                            <p>{{ \Illuminate\Support\Str::limit($log->user_agent ?: '-', 160) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="admin-user-detail-block h-100">
                                            <span>Gerekce</span>
                                            <p>{{ $log->reason ?: '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <div class="admin-user-detail-block">
                                            <span>Eski Deger</span>
                                            <pre class="admin-json-block">{{ json_encode($log->old_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="admin-user-detail-block">
                                            <span>Yeni Deger</span>
                                            <pre class="admin-json-block">{{ json_encode($log->new_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kapat</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </section>
@endsection
