<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Get SMTP settings
        $smtpPassword = Setting::get('smtp_password', '');
        // If password exists but is empty string, it means it's set but we don't show it
        $smtpSettings = [
            'smtp_host' => Setting::get('smtp_host', ''),
            'smtp_port' => Setting::get('smtp_port', '587'),
            'smtp_username' => Setting::get('smtp_username', ''),
            'smtp_password' => $smtpPassword ? '••••••••' : '', // Show dots if password exists
            'smtp_encryption' => Setting::get('smtp_encryption', 'tls'),
            'smtp_from_email' => Setting::get('smtp_from_email', ''),
            'smtp_from_name' => Setting::get('smtp_from_name', 'DMC'),
        ];

        // Get Firebase settings
        $firebaseSettings = [
            'firebase_api_key' => Setting::get('firebase_api_key', ''),
            'firebase_project_id' => Setting::get('firebase_project_id', ''),
            'firebase_sender_id' => Setting::get('firebase_sender_id', ''),
            'firebase_app_id' => Setting::get('firebase_app_id', ''),
            'firebase_credentials_json' => Setting::get('firebase_credentials_json', ''),
        ];

        // Get Razorpay settings
        $razorpayKeySecret = Setting::get('razorpay_key_secret', '');
        $razorpayWebhookSecret = Setting::get('razorpay_webhook_secret', '');
        $razorpaySettings = [
            'razorpay_key_id' => Setting::get('razorpay_key_id', ''),
            'razorpay_key_secret' => $razorpayKeySecret ? '••••••••' : '', // Show dots if secret exists
            'razorpay_webhook_secret' => $razorpayWebhookSecret ? '••••••••' : '', // Show dots if secret exists
            'razorpay_environment' => Setting::get('razorpay_environment', 'test'), // test or live
            'razorpay_enabled' => Setting::get('razorpay_enabled', '0'), // 0 or 1
        ];

        return view('admin.settings.index', compact('smtpSettings', 'firebaseSettings', 'razorpaySettings'));
    }

    /**
     * Update SMTP settings.
     */
    public function updateSmtp(Request $request)
    {
        $existingPassword = Setting::get('smtp_password', '');
        $passwordRequired = empty($existingPassword) ? 'required' : 'nullable';
        
        $validator = Validator::make($request->all(), [
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => $passwordRequired . '|string|max:255',
            'smtp_encryption' => 'required|in:tls,ssl',
            'smtp_from_email' => 'required|email|max:255',
            'smtp_from_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the validation errors.');
        }

        // Save SMTP settings
        Setting::set('smtp_host', $request->smtp_host);
        Setting::set('smtp_port', $request->smtp_port);
        Setting::set('smtp_username', $request->smtp_username);
        
        // Only update password if it's provided and not empty (and not placeholder)
        if (!empty($request->smtp_password) && $request->smtp_password !== '••••••••') {
            Setting::set('smtp_password', $request->smtp_password, 'encrypted');
        }
        
        Setting::set('smtp_encryption', $request->smtp_encryption);
        Setting::set('smtp_from_email', $request->smtp_from_email);
        Setting::set('smtp_from_name', $request->smtp_from_name);

        // Update .env file or config
        $this->updateEnvConfig($request->all(), 'smtp');

        return redirect()->route('admin.settings.index')
            ->with('success', 'SMTP settings updated successfully.');
    }

    /**
     * Update Firebase settings.
     */
    public function updateFirebase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_api_key' => 'required|string|max:255',
            'firebase_project_id' => 'required|string|max:255',
            'firebase_sender_id' => 'required|string|max:255',
            'firebase_app_id' => 'required|string|max:255',
            'firebase_credentials_json' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the validation errors.');
        }

        // Save Firebase settings
        Setting::set('firebase_api_key', $request->firebase_api_key, 'encrypted');
        Setting::set('firebase_project_id', $request->firebase_project_id);
        Setting::set('firebase_sender_id', $request->firebase_sender_id);
        Setting::set('firebase_app_id', $request->firebase_app_id);
        
        if ($request->firebase_credentials_json) {
            Setting::set('firebase_credentials_json', $request->firebase_credentials_json, 'encrypted');
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Firebase settings updated successfully.');
    }

    /**
     * Update Razorpay payment settings.
     */
    public function updateRazorpay(Request $request)
    {
        $existingKeySecret = Setting::get('razorpay_key_secret', '');
        $existingWebhookSecret = Setting::get('razorpay_webhook_secret', '');
        
        $keySecretRequired = empty($existingKeySecret) ? 'required' : 'nullable';
        $webhookSecretRequired = 'nullable';

        $validator = Validator::make($request->all(), [
            'razorpay_key_id' => 'required|string|max:255',
            'razorpay_key_secret' => $keySecretRequired . '|string|max:255',
            'razorpay_webhook_secret' => $webhookSecretRequired . '|string|max:255',
            'razorpay_environment' => 'required|in:test,live',
            'razorpay_enabled' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the validation errors.');
        }

        // Save Razorpay settings
        Setting::set('razorpay_key_id', $request->razorpay_key_id);
        
        // Only update key secret if it's provided and not empty (and not placeholder)
        if (!empty($request->razorpay_key_secret) && $request->razorpay_key_secret !== '••••••••') {
            Setting::set('razorpay_key_secret', $request->razorpay_key_secret, 'encrypted');
        }
        
        // Only update webhook secret if it's provided and not empty (and not placeholder)
        if (!empty($request->razorpay_webhook_secret) && $request->razorpay_webhook_secret !== '••••••••') {
            Setting::set('razorpay_webhook_secret', $request->razorpay_webhook_secret, 'encrypted');
        }
        
        Setting::set('razorpay_environment', $request->razorpay_environment);
        Setting::set('razorpay_enabled', $request->has('razorpay_enabled') ? $request->razorpay_enabled : '0');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Razorpay payment settings updated successfully.');
    }

    /**
     * Update environment configuration.
     */
    private function updateEnvConfig($data, $type)
    {
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            return;
        }

        $envContent = file_get_contents($envFile);

        if ($type === 'smtp') {
            $envContent = preg_replace('/^MAIL_HOST=.*/m', 'MAIL_HOST=' . $data['smtp_host'], $envContent);
            $envContent = preg_replace('/^MAIL_PORT=.*/m', 'MAIL_PORT=' . $data['smtp_port'], $envContent);
            $envContent = preg_replace('/^MAIL_USERNAME=.*/m', 'MAIL_USERNAME=' . $data['smtp_username'], $envContent);
            $envContent = preg_replace('/^MAIL_PASSWORD=.*/m', 'MAIL_PASSWORD=' . $data['smtp_password'], $envContent);
            $envContent = preg_replace('/^MAIL_ENCRYPTION=.*/m', 'MAIL_ENCRYPTION=' . $data['smtp_encryption'], $envContent);
            $envContent = preg_replace('/^MAIL_FROM_ADDRESS=.*/m', 'MAIL_FROM_ADDRESS=' . $data['smtp_from_email'], $envContent);
            $envContent = preg_replace('/^MAIL_FROM_NAME=.*/m', 'MAIL_FROM_NAME="' . $data['smtp_from_name'] . '"', $envContent);
        }

        file_put_contents($envFile, $envContent);
    }
}
