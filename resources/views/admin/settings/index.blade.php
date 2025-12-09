@extends('admin.layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
    <style>
        .settings-container {
            display: grid;
            gap: 30px;
        }

        .settings-tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .tab-button {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #718096;
            transition: all 0.3s;
        }

        .tab-button:hover {
            color: #667eea;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .settings-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e53e3e;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            font-family: monospace;
        }

        .form-help {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }

        .btn-save {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-save:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 0;
            color: #2c5282;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #2c5282;
        }
    </style>

    <div class="settings-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div class="settings-tabs" style="flex: 1;">
                <button class="tab-button active" onclick="switchTab('smtp')">
                    📧 SMTP Settings
                </button>
                <button class="tab-button" onclick="switchTab('firebase')">
                    🔥 Firebase Settings
                </button>
                <button class="tab-button" onclick="switchTab('razorpay')">
                    💳 Payment Configurations
                </button>
            </div>
            <div style="margin-left: 20px;">
                <form action="{{ route('admin.cache.clear.all') }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all caches?');">
                    @csrf
                    <button type="submit" style="background: #e53e3e; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                        🧹 Clear All Cache
                    </button>
                </form>
            </div>
        </div>

        <!-- SMTP Settings Tab -->
        <div id="smtp-tab" class="tab-content active">
            <div class="settings-form">
                <div class="info-box">
                    <strong>SMTP Configuration</strong>
                    <p>Configure your SMTP settings to send OTP via email. These credentials will be used to send verification codes to users' email addresses.</p>
                </div>

                <form action="{{ route('admin.settings.smtp.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-section">
                        <h3 class="form-section-title">Server Configuration</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    SMTP Host <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="smtp_host" 
                                       value="{{ old('smtp_host', $smtpSettings['smtp_host']) }}" 
                                       placeholder="smtp.gmail.com"
                                       required>
                                <div class="form-help">Your SMTP server hostname</div>
                                @error('smtp_host')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    SMTP Port <span class="required">*</span>
                                </label>
                                <input type="number" 
                                       name="smtp_port" 
                                       value="{{ old('smtp_port', $smtpSettings['smtp_port']) }}" 
                                       placeholder="587"
                                       min="1"
                                       max="65535"
                                       required>
                                <div class="form-help">Common ports: 587 (TLS), 465 (SSL), 25</div>
                                @error('smtp_port')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    Encryption <span class="required">*</span>
                                </label>
                                <select name="smtp_encryption" required>
                                    <option value="tls" {{ old('smtp_encryption', $smtpSettings['smtp_encryption']) == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('smtp_encryption', $smtpSettings['smtp_encryption']) == 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                                <div class="form-help">Encryption method for SMTP connection</div>
                                @error('smtp_encryption')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Authentication</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    SMTP Username <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="smtp_username" 
                                       value="{{ old('smtp_username', $smtpSettings['smtp_username']) }}" 
                                       placeholder="your-email@gmail.com"
                                       required>
                                <div class="form-help">Your SMTP account username/email</div>
                                @error('smtp_username')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    SMTP Password <span class="required">*</span>
                                </label>
                                <input type="password" 
                                       name="smtp_password" 
                                       value="{{ old('smtp_password', $smtpSettings['smtp_password'] === '••••••••' ? '' : $smtpSettings['smtp_password']) }}" 
                                       placeholder="{{ $smtpSettings['smtp_password'] === '••••••••' ? 'Leave blank to keep current password' : 'Your SMTP password' }}"
                                       {{ $smtpSettings['smtp_password'] === '••••••••' ? '' : 'required' }}>
                                <div class="form-help">
                                    {{ $smtpSettings['smtp_password'] === '••••••••' ? 'Leave blank to keep current password, or enter new password' : 'Your SMTP account password or app password' }}
                                </div>
                                @error('smtp_password')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Email Settings</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    From Email <span class="required">*</span>
                                </label>
                                <input type="email" 
                                       name="smtp_from_email" 
                                       value="{{ old('smtp_from_email', $smtpSettings['smtp_from_email']) }}" 
                                       placeholder="noreply@dmc.com"
                                       required>
                                <div class="form-help">Email address that will appear as sender</div>
                                @error('smtp_from_email')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    From Name <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="smtp_from_name" 
                                       value="{{ old('smtp_from_name', $smtpSettings['smtp_from_name']) }}" 
                                       placeholder="DMC"
                                       required>
                                <div class="form-help">Name that will appear as sender</div>
                                @error('smtp_from_name')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-save">Save SMTP Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Firebase Settings Tab -->
        <div id="firebase-tab" class="tab-content">
            <div class="settings-form">
                <div class="info-box">
                    <strong>Firebase Configuration</strong>
                    <p>Configure your Firebase settings to send OTP via SMS. You need to set up Firebase Cloud Messaging (FCM) and get your credentials from Firebase Console.</p>
                </div>

                <form action="{{ route('admin.settings.firebase.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-section">
                        <h3 class="form-section-title">Firebase Credentials</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    Firebase API Key <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="firebase_api_key" 
                                       value="{{ old('firebase_api_key', $firebaseSettings['firebase_api_key']) }}" 
                                       placeholder="AIzaSy..."
                                       required>
                                <div class="form-help">Your Firebase Web API Key</div>
                                @error('firebase_api_key')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    Project ID <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="firebase_project_id" 
                                       value="{{ old('firebase_project_id', $firebaseSettings['firebase_project_id']) }}" 
                                       placeholder="your-project-id"
                                       required>
                                <div class="form-help">Your Firebase Project ID</div>
                                @error('firebase_project_id')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    Sender ID <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="firebase_sender_id" 
                                       value="{{ old('firebase_sender_id', $firebaseSettings['firebase_sender_id']) }}" 
                                       placeholder="123456789"
                                       required>
                                <div class="form-help">Firebase Cloud Messaging Sender ID</div>
                                @error('firebase_sender_id')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    App ID <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="firebase_app_id" 
                                       value="{{ old('firebase_app_id', $firebaseSettings['firebase_app_id']) }}" 
                                       placeholder="1:123456789:web:abc123"
                                       required>
                                <div class="form-help">Firebase App ID</div>
                                @error('firebase_app_id')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Service Account Credentials (Optional)</h3>
                        <div class="form-group">
                            <label>
                                Service Account JSON
                            </label>
                            <textarea name="firebase_credentials_json" 
                                      placeholder='{"type":"service_account","project_id":"..."}'
                                      rows="8">{{ old('firebase_credentials_json', $firebaseSettings['firebase_credentials_json']) }}</textarea>
                            <div class="form-help">Paste your Firebase Service Account JSON credentials here (optional, for advanced features)</div>
                            @error('firebase_credentials_json')
                                <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-save">Save Firebase Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Razorpay Payment Settings Tab -->
        <div id="razorpay-tab" class="tab-content">
            <div class="settings-form">
                <div class="info-box">
                    <strong>Razorpay Payment Configuration</strong>
                    <p>Configure your Razorpay payment gateway settings to enable online payments. You can get your API keys from the Razorpay Dashboard. Make sure to use test keys for testing and live keys for production.</p>
                </div>

                <form action="{{ route('admin.settings.razorpay.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-section">
                        <h3 class="form-section-title">Payment Gateway Status</h3>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                <input type="checkbox" 
                                       name="razorpay_enabled" 
                                       value="1"
                                       {{ old('razorpay_enabled', $razorpaySettings['razorpay_enabled']) == '1' ? 'checked' : '' }}
                                       style="width: 20px; height: 20px; cursor: pointer;">
                                <span>Enable Razorpay Payment Gateway</span>
                            </label>
                            <div class="form-help">Enable or disable Razorpay payment processing</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">API Credentials</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    Razorpay Key ID <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="razorpay_key_id" 
                                       value="{{ old('razorpay_key_id', $razorpaySettings['razorpay_key_id']) }}" 
                                       placeholder="rzp_test_..."
                                       required>
                                <div class="form-help">Your Razorpay Key ID (starts with rzp_test_ or rzp_live_)</div>
                                @error('razorpay_key_id')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    Razorpay Key Secret <span class="required">*</span>
                                </label>
                                <input type="password" 
                                       name="razorpay_key_secret" 
                                       value="{{ old('razorpay_key_secret', $razorpaySettings['razorpay_key_secret'] === '••••••••' ? '' : $razorpaySettings['razorpay_key_secret']) }}" 
                                       placeholder="{{ $razorpaySettings['razorpay_key_secret'] === '••••••••' ? 'Leave blank to keep current secret' : 'Your Razorpay Key Secret' }}"
                                       {{ $razorpaySettings['razorpay_key_secret'] === '••••••••' ? '' : 'required' }}>
                                <div class="form-help">
                                    {{ $razorpaySettings['razorpay_key_secret'] === '••••••••' ? 'Leave blank to keep current secret, or enter new secret' : 'Your Razorpay Key Secret (keep this secure)' }}
                                </div>
                                @error('razorpay_key_secret')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    Environment <span class="required">*</span>
                                </label>
                                <select name="razorpay_environment" required>
                                    <option value="test" {{ old('razorpay_environment', $razorpaySettings['razorpay_environment']) == 'test' ? 'selected' : '' }}>Test Mode</option>
                                    <option value="live" {{ old('razorpay_environment', $razorpaySettings['razorpay_environment']) == 'live' ? 'selected' : '' }}>Live Mode</option>
                                </select>
                                <div class="form-help">Use Test Mode for development, Live Mode for production</div>
                                @error('razorpay_environment')
                                    <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Webhook Configuration (Optional)</h3>
                        <div class="form-group">
                            <label>
                                Webhook Secret
                            </label>
                            <input type="password" 
                                   name="razorpay_webhook_secret" 
                                   value="{{ old('razorpay_webhook_secret', $razorpaySettings['razorpay_webhook_secret'] === '••••••••' ? '' : $razorpaySettings['razorpay_webhook_secret']) }}" 
                                   placeholder="{{ $razorpaySettings['razorpay_webhook_secret'] === '••••••••' ? 'Leave blank to keep current secret' : 'Your Razorpay Webhook Secret' }}">
                            <div class="form-help">
                                {{ $razorpaySettings['razorpay_webhook_secret'] === '••••••••' ? 'Leave blank to keep current secret, or enter new webhook secret' : 'Webhook secret for verifying Razorpay webhook requests (optional but recommended)' }}
                            </div>
                            @error('razorpay_webhook_secret')
                                <div style="color: #e53e3e; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-section">
                        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                            <strong style="display: block; margin-bottom: 8px; color: #856404;">⚠️ Important Security Notes:</strong>
                            <ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 13px; line-height: 1.8;">
                                <li>Never share your Razorpay Key Secret or Webhook Secret publicly</li>
                                <li>Use Test Mode during development and Live Mode only in production</li>
                                <li>Make sure your webhook URL is configured in Razorpay Dashboard</li>
                                <li>Test payments in Test Mode before going live</li>
                            </ul>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-save">Save Payment Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
@endsection

