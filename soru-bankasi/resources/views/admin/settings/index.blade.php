@extends('layouts.admin', ['pageTitle' => 'Ayarlar', 'title' => 'Ayarlar'])

@section('content')
    <section class="admin-command-hero">
        <div>
            <p class="eyebrow">Sistem Kontrol Merkezi</p>
            <h1>Sistem ayarlari</h1>
            <p>Kayit, giris, puanlama, leaderboard, itiraz, oneri ve arsiv davranislarini tek merkezden yonetin.</p>
        </div>
        <div class="admin-command-score">
            <span>Ayar Grubu</span>
            <strong>{{ count($groups) }}</strong>
            <small>aktif konfigurasyon alani</small>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="admin-settings-grid">
            @foreach($groups as $groupKey => $settings)
                <section class="admin-panel">
                    <div class="admin-panel__head">
                        <div>
                            <h2>{{ $groupLabels[$groupKey] ?? $groupKey }}</h2>
                            <p>Bu bolumdeki degisiklikler ilgili akislara aninda uygulanir.</p>
                        </div>
                    </div>
                    <div class="admin-panel__content">
                        <div class="row g-3">
                            @foreach($settings as $key => $setting)
                                @php($value = old($key, $setting['value']))
                                <div class="{{ $setting['type'] === 'text' ? 'col-12' : 'col-md-6' }}">
                                    <label for="{{ $key }}" class="form-label fw-semibold">{{ $setting['label'] }}</label>

                                    @if($setting['type'] === 'boolean')
                                        <select name="{{ $key }}" id="{{ $key }}" class="form-select" required>
                                            <option value="1" @selected((bool) $value === true)>Acik</option>
                                            <option value="0" @selected((bool) $value === false)>Kapali</option>
                                        </select>
                                    @elseif($key === 'test_feedback_mode')
                                        <select name="{{ $key }}" id="{{ $key }}" class="form-select" required>
                                            @foreach(['DELAYED_FEEDBACK' => 'Test sonunda goster', 'INSTANT_FEEDBACK_LOCKED' => 'Aninda goster ve kilitle', 'NO_FEEDBACK' => 'Feedback kapali'] as $optionValue => $label)
                                                <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($key === 'backup_mode')
                                        <select name="{{ $key }}" id="{{ $key }}" class="form-select" required>
                                            <option value="manual" @selected($value === 'manual')>Manual</option>
                                            <option value="automatic" @selected($value === 'automatic')>Automatic</option>
                                        </select>
                                    @elseif($setting['type'] === 'text')
                                        <textarea name="{{ $key }}" id="{{ $key }}" rows="{{ $key === 'question_report_accept_message' ? 4 : 3 }}" maxlength="1000" class="form-control" required>{{ $value }}</textarea>
                                        @if($key === 'question_report_accept_message')
                                            <div class="form-text">Kullanilabilir alanlar: {old_answer}, {new_answer}, {question_id}</div>
                                        @endif
                                    @else
                                        <input type="number" name="{{ $key }}" id="{{ $key }}" class="form-control" value="{{ $value }}" required>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <section class="admin-panel mt-4">
            <div class="admin-panel__content">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="current_password" class="form-label fw-semibold">Admin sifresi</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="col-lg-6 d-flex justify-content-lg-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Ayarlari Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </form>
@endsection
