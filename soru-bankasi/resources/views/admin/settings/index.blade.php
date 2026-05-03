@extends('layouts.admin', ['pageTitle' => 'Ayarlar', 'title' => 'Ayarlar'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Sistem Ayarlari</h1>
        <p class="text-muted mb-0">Test davranisi, kayit, limit ve operasyon ayarlarini yonetin.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label for="test_feedback_mode" class="form-label">{{ $settings['test_feedback_mode']['label'] }}</label>
                    <select name="test_feedback_mode" id="test_feedback_mode" class="form-select" required>
                        @foreach(['DELAYED_FEEDBACK' => 'Delayed feedback', 'INSTANT_FEEDBACK_LOCKED' => 'Instant feedback locked', 'NO_FEEDBACK' => 'No feedback'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('test_feedback_mode', $settings['test_feedback_mode']['value']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="registration_open" class="form-label">{{ $settings['registration_open']['label'] }}</label>
                    <select name="registration_open" id="registration_open" class="form-select" required>
                        <option value="1" @selected((bool) old('registration_open', $settings['registration_open']['value']) === true)>Acik</option>
                        <option value="0" @selected((bool) old('registration_open', $settings['registration_open']['value']) === false)>Kapali</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="maintenance_mode" class="form-label">{{ $settings['maintenance_mode']['label'] }}</label>
                    <select name="maintenance_mode" id="maintenance_mode" class="form-select" required>
                        <option value="0" @selected((bool) old('maintenance_mode', $settings['maintenance_mode']['value']) === false)>Kapali</option>
                        <option value="1" @selected((bool) old('maintenance_mode', $settings['maintenance_mode']['value']) === true)>Acik</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="daily_test_limit" class="form-label">{{ $settings['daily_test_limit']['label'] }}</label>
                    <input type="number" min="1" max="100" name="daily_test_limit" id="daily_test_limit" class="form-control" value="{{ old('daily_test_limit', $settings['daily_test_limit']['value']) }}" required>
                </div>

                <div class="col-md-3">
                    <label for="daily_question_limit" class="form-label">{{ $settings['daily_question_limit']['label'] }}</label>
                    <input type="number" min="1" max="100" name="daily_question_limit" id="daily_question_limit" class="form-control" value="{{ old('daily_question_limit', $settings['daily_question_limit']['value']) }}" required>
                </div>

                <div class="col-md-3">
                    <label for="login_rate_limit" class="form-label">{{ $settings['login_rate_limit']['label'] }}</label>
                    <input type="number" min="1" max="20" name="login_rate_limit" id="login_rate_limit" class="form-control" value="{{ old('login_rate_limit', $settings['login_rate_limit']['value']) }}" required>
                </div>

                <div class="col-md-3">
                    <label for="login_lockout_duration" class="form-label">{{ $settings['login_lockout_duration']['label'] }}</label>
                    <input type="number" min="60" max="86400" name="login_lockout_duration" id="login_lockout_duration" class="form-control" value="{{ old('login_lockout_duration', $settings['login_lockout_duration']['value']) }}" required>
                </div>

                <div class="col-md-3">
                    <label for="minimum_leaderboard_tests" class="form-label">{{ $settings['minimum_leaderboard_tests']['label'] }}</label>
                    <input type="number" min="1" max="20" name="minimum_leaderboard_tests" id="minimum_leaderboard_tests" class="form-control" value="{{ old('minimum_leaderboard_tests', $settings['minimum_leaderboard_tests']['value']) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="backup_mode" class="form-label">{{ $settings['backup_mode']['label'] }}</label>
                    <select name="backup_mode" id="backup_mode" class="form-select" required>
                        <option value="manual" @selected(old('backup_mode', $settings['backup_mode']['value']) === 'manual')>Manual</option>
                        <option value="automatic" @selected(old('backup_mode', $settings['backup_mode']['value']) === 'automatic')>Automatic</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="current_password" class="form-label">Admin sifresi</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Ayarlari Kaydet</button>
                </div>
            </form>
        </div>
    </div>
@endsection
