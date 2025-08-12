# Email Template Fix Guide

## Issue Analysis
The email is showing raw Blade syntax (`@component('mail::message')`) instead of rendered HTML because:

1. **Mailer Configuration**: The mailer is set to SMTP but SMTP credentials are not configured
2. **Template Processing**: The template is being processed but not sent as HTML
3. **Route Issues**: The original template had an incorrect route reference

## ✅ Fixes Applied

### 1. Fixed Email Template Structure
- ✅ Changed greeting to address approvers instead of applicants
- ✅ Added proper Markdown formatting with `##` headers
- ✅ Fixed field references (`$leaveApplication->leave_days`)
- ✅ Added proper date formatting with Carbon
- ✅ Fixed route reference to use existing `leave.index` route

### 2. Current Email Template
```php
@component('mail::message')
# New Leave Application Submitted

Hello,

A new leave application has been submitted and is awaiting your review.

## Application Details

- **Employee:** {{ $leaveApplication->user->name }}
- **Leave Type:** {{ ucfirst($leaveApplication->leave_type) }}
- **Start Date:** {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }}
- **End Date:** {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}
- **Duration:** {{ $leaveApplication->leave_days }} day(s)
- **Day Type:** {{ ucfirst(str_replace('_', ' ', $leaveApplication->day_type ?? 'full_day')) }}
- **Reason:** {{ $leaveApplication->reason }}

**Leave Period:** {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}

Please review this application and take appropriate action.

@component('mail::button', ['url' => route('leave.index')])
Review Applications
@endcomponent

Thanks,<br>
{{ config('app.name') }}

@endcomponent
```

## 🚀 Solution Steps

### Option 1: Configure Mailtrap (Recommended)

1. **Create/Update `.env` file** with these settings:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="${APP_NAME}"
```

2. **Get Mailtrap credentials**:
   - Go to [Mailtrap.io](https://mailtrap.io)
   - Create account or login
   - Go to your Inbox
   - Click "Show Credentials"
   - Copy SMTP settings

3. **Clear and cache config**:
```bash
php artisan config:clear
php artisan config:cache
```

### Option 2: Use Array Driver for Testing

1. **Set mailer to array** in your `.env`:
```env
MAIL_MAILER=array
```

2. **Test email sending**:
```bash
php artisan test:email-template --send
```

3. **Check sent emails** (if using array driver):
```bash
php artisan check:sent-emails
```

### Option 3: Use Log Driver for Development

1. **Set mailer to log** in your `.env`:
```env
MAIL_MAILER=log
```

2. **Check log files** for email content:
```bash
tail -f storage/logs/laravel.log
```

## 🧪 Testing Commands

### Test Email Template Rendering
```bash
php artisan test:email-template
```

### Test Email Sending
```bash
php artisan test:email-template --send
```

### Check Mail Configuration
```bash
php artisan config:show mail.default
```

## 📧 Expected Email Output

Once properly configured, the email should show:
- ✅ Professional HTML layout
- ✅ Employee name and details
- ✅ Formatted dates and duration
- ✅ Leave type and reason
- ✅ Action button to review applications
- ✅ Company branding

## 🔧 Troubleshooting

### If emails still show raw Blade syntax:
1. Check mailer configuration: `php artisan config:show mail`
2. Verify template rendering: `php artisan test:email-template`
3. Check for syntax errors in template
4. Ensure proper Markdown formatting

### If emails are not being sent:
1. Check SMTP credentials
2. Verify network connectivity
3. Check mailer logs
4. Test with array driver first

## ✅ Verification

The email template is now properly structured and should render correctly once the mailer is configured. The template includes:
- Proper Markdown formatting
- Correct field references
- Valid route links
- Professional layout
- Complete application details
