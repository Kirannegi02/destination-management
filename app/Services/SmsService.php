<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    /**
     * Send OTP via SMS using Firebase Phone Auth or other service
     * 
     * Note: Firebase Phone Auth requires client-side SDK to send SMS.
     * This method provides Firebase config for client-side implementation.
     * 
     * @param string $phoneNumber Phone number in E.164 format (e.g., +1234567890)
     * @param string $otp The OTP code to send
     * @return array Result with status and message
     */
    public function sendOtp(string $phoneNumber, string $otp): array
    {
        try {
            // Get Firebase settings
            $firebaseProjectId = Setting::get('firebase_project_id', '');
            $firebaseApiKey = Setting::get('firebase_api_key', '');
            $firebaseSenderId = Setting::get('firebase_sender_id', '');
            $firebaseAppId = Setting::get('firebase_app_id', '');
            
            if (empty($firebaseApiKey) || empty($firebaseProjectId)) {
                Log::warning('Firebase credentials not configured for SMS');
                return [
                    'success' => false,
                    'message' => 'Firebase not configured. SMS cannot be sent.',
                    'requires_client_sdk' => true,
                ];
            }

            // Firebase Phone Auth requires client-side SDK
            // We cannot send SMS directly from server-side
            // Return Firebase config so client can use Firebase SDK
            return [
                'success' => true,
                'message' => 'Firebase config provided. Client must use Firebase SDK to send SMS.',
                'requires_client_sdk' => true,
                'firebase_config' => [
                    'apiKey' => $firebaseApiKey,
                    'authDomain' => $firebaseProjectId . '.firebaseapp.com',
                    'projectId' => $firebaseProjectId,
                    'storageBucket' => $firebaseProjectId . '.firebasestorage.app',
                    'messagingSenderId' => $firebaseSenderId,
                    'appId' => $firebaseAppId,
                ],
                'phone_number' => $phoneNumber,
                'note' => 'Use Firebase SDK signInWithPhoneNumber() method to send SMS. The OTP is generated and stored in database for verification.',
            ];
        } catch (\Exception $e) {
            Log::error('SMS service error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
            ];
        }
    }
}



