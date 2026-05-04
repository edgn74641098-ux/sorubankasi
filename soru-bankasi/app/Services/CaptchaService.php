<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CaptchaService
{
    private const SESSION_KEY = 'captcha.answer_hash';

    private const SESSION_PROMPT = 'captcha.prompt';

    public function challenge(Request $request): array
    {
        $prompt = $request->session()->get(self::SESSION_PROMPT);

        if (! $prompt) {
            $prompt = $this->refresh($request);
        }

        return [
            'prompt' => $prompt,
            'svg' => $this->svgDataUri($prompt),
        ];
    }

    public function refresh(Request $request): string
    {
        $left = random_int(2, 9);
        $right = random_int(1, 9);
        $prompt = "{$left} + {$right} = ?";

        $request->session()->put(self::SESSION_PROMPT, $prompt);
        $request->session()->put(self::SESSION_KEY, Hash::make((string) ($left + $right)));

        return $prompt;
    }

    public function validate(Request $request, ?string $answer): bool
    {
        $hash = $request->session()->get(self::SESSION_KEY);

        if (! $hash || ! Hash::check(trim((string) $answer), $hash)) {
            $this->refresh($request);

            return false;
        }

        $request->session()->forget([self::SESSION_KEY, self::SESSION_PROMPT]);

        return true;
    }

    private function svgDataUri(string $prompt): string
    {
        $noise = collect(range(1, 8))
            ->map(fn () => '<line x1="'.random_int(0, 180).'" y1="'.random_int(0, 54).'" x2="'.random_int(0, 180).'" y2="'.random_int(0, 54).'" stroke="#cbd5e1" stroke-width="1"/>')
            ->implode('');

        $escaped = e($prompt);
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="180" height="54" viewBox="0 0 180 54">
  <rect width="180" height="54" rx="12" fill="#f8fafc"/>
  {$noise}
  <text x="90" y="35" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" font-weight="700" fill="#0f172a">{$escaped}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode(Str::of($svg)->squish()->toString());
    }
}
