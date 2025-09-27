<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private function ok(array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    private function fail(string $code, string $message, int $status = 400, $details = null)
    {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
        if ($details !== null) {
            $payload['error']['details'] = $details;
        }
        return response()->json($payload, $status);
    }
    public function requestOtp(Request $request, OtpService $otp)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'string', 'min:6', 'max:20'],
            ]);
            if ($validator->fails()) {
                return $this->fail('validation_error', 'Invalid input', 422, $validator->errors());
            }

            $result = $otp->requestCode($validator->validated()['phone']);

            return $this->ok([
                'message' => 'OTP sent',
                'phone' => $result['phone'],
                'expires_at' => $result['expires_at'],
            ]);
        } catch (\Throwable $e) {
            return $this->fail('otp_request_failed', config('app.debug') ? $e->getMessage() : 'Unable to send OTP at this time', 500);
        }
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'string', 'min:6', 'max:20'],
                'code' => ['required', 'string', 'size:6'],
            ]);
            if ($validator->fails()) {
                return $this->fail('validation_error', 'Invalid input', 422, $validator->errors());
            }

            $v = $validator->validated();
            $verification = $otp->verifyCode($v['phone'], $v['code']);

            if (!$verification['ok']) {
                $map = [
                    'otp_not_requested' => [404, 'OTP not requested for this phone'],
                    'otp_expired' => [410, 'OTP expired, request a new one'],
                    'too_many_attempts' => [429, 'Too many incorrect attempts, request a new code'],
                    'invalid_code' => [422, 'Invalid code'],
                ];
                [$status, $message] = $map[$verification['error']] ?? [400, 'OTP verification failed'];
                return $this->fail($verification['error'], $message, $status);
            }

            $user = $verification['user'];
            $token = $user->createToken('api')->plainTextToken;

            return $this->ok([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail('otp_verify_failed', config('app.debug') ? $e->getMessage() : 'OTP verification failed', 500);
        }
    }
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);
            if ($validator->fails()) {
                return $this->fail('validation_error', 'Invalid input', 422, $validator->errors());
            }

            $v = $validator->validated();
            $user = User::create([
                'name' => $v['name'],
                'email' => $v['email'],
                'password' => Hash::make($v['password']),
            ]);

            return $this->ok([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return $this->fail('register_failed', config('app.debug') ? $e->getMessage() : 'Registration failed', 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);
            if ($validator->fails()) {
                return $this->fail('validation_error', 'Invalid input', 422, $validator->errors());
            }

            $credentials = $validator->validated();
            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return $this->fail('invalid_credentials', 'Incorrect email or password', 401);
            }

            $token = $user->createToken('api')->plainTextToken;

            return $this->ok([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail('login_failed', config('app.debug') ? $e->getMessage() : 'Login failed', 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user) {
                $user->currentAccessToken()?->delete();
            }
            return $this->ok(['message' => 'Logged out']);
        } catch (\Throwable $e) {
            return $this->fail('logout_failed', config('app.debug') ? $e->getMessage() : 'Logout failed', 500);
        }
    }

    public function guest(Request $request)
    {
        try {
            $name = $request->input('name', 'Guest');
            $user = User::create([
                'name' => $name,
                'email' => 'guest_'.uniqid().'@placeholder.local',
                'password' => \Illuminate\Support\Str::random(32),
                'is_guest' => true,
            ]);

            $token = $user->createToken('api')->plainTextToken;

            return $this->ok([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_guest' => $user->is_guest,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return $this->fail('guest_creation_failed', config('app.debug') ? $e->getMessage() : 'Unable to create guest user', 500);
        }
    }
}


