<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtpService
{
    public function requestCode(string $rawPhone, int $ttlSeconds = 300): array
    {
        $phone = $this->normalizePhone($rawPhone);

        // Rate limit: allow one active code per phone; replace if expired
        $now = CarbonImmutable::now();

        // Delete old codes for this phone to keep table small
        OtpCode::where('phone', $phone)->where('expires_at', '<', $now)->delete();

        $code = $this->generateCode();
        $expiresAt = $now->addSeconds($ttlSeconds);

        OtpCode::updateOrCreate(
            ['phone' => $phone],
            ['code' => $code, 'attempts' => 0, 'expires_at' => $expiresAt, 'created_at' => $now]
        );

        // TODO integrate with SMS provider. For now, log the code for dev/testing.
        Log::info('OTP sent', ['phone' => $phone, 'code' => $code]);

        return ['phone' => $phone, 'expires_at' => $expiresAt];
    }

    public function verifyCode(string $rawPhone, string $code): array
    {
        $phone = $this->normalizePhone($rawPhone);
        $record = OtpCode::where('phone', $phone)->first();

        if (!$record) {
            return ['ok' => false, 'error' => 'otp_not_requested'];
        }

        $now = CarbonImmutable::now();
        if ($record->expires_at < $now) {
            $record->delete();
            return ['ok' => false, 'error' => 'otp_expired'];
        }

        if (!hash_equals((string) $record->code, (string) $code)) {
            $record->attempts = (int) $record->attempts + 1;
            // lockout after 5 attempts
            if ($record->attempts >= 5) {
                $record->delete();
                return ['ok' => false, 'error' => 'too_many_attempts'];
            }
            $record->save();
            return ['ok' => false, 'error' => 'invalid_code'];
        }

        // success: delete code
        $record->delete();

        // find or create user by phone
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            $user = User::create([
                'name' => 'User '.Str::substr($phone, -4),
                'email' => $phone.'@placeholder.local',
                'password' => Str::random(32),
                'phone' => $phone,
                'phone_verified_at' => $now,
            ]);
        } else {
            if (empty($user->phone_verified_at)) {
                $user->phone_verified_at = $now;
                $user->save();
            }
        }

        return ['ok' => true, 'user' => $user];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone);
        // simple normalization: ensure starts with + or 0; for production, use libphonenumber
        if ($digits && $digits[0] !== '+') {
            if ($digits[0] !== '0') {
                $digits = '+'.$digits;
            }
        }
        return $digits;
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}


