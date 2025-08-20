# Mailtrap Setup Guide

## Issue
The email templates are showing raw Blade syntax instead of rendered HTML because the mailer is configured to 'log' instead of sending actual emails.

## Solution

### 1. Configure Mailtrap SMTP Settings

Add these environment variables to your `.env` file:

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

### 2. Get Mailtrap Credentials

1. Go to [Mailtrap.io](https://mailtrap.io)
2. Create an account or log in
3. Go to your Inbox
4. Click on "Show Credentials"
5. Copy the SMTP settings

### 3. Test Email Configuration

After updating your `.env` file, run:

```bash
php artisan config:clear
php artisan config:cache
```

### 4. Test Email Sending

You can test email sending with:

```bash
php artisan test:email-template
```

### 5. Alternative: Use Array Driver for Testing

If you want to test without Mailtrap, you can use the array driver:

```env
MAIL_MAILER=array
```

Then emails will be stored in the application and you can retrieve them programmatically.

## Email Template Fixes Applied

✅ **Fixed email template structure** - The template now properly addresses approvers instead of applicants  
✅ **Added proper application details** - Shows employee name, leave type, dates, duration, etc.  
✅ **Fixed field references** - Changed `$leaveApplication->duration` to `$leaveApplication->leave_days`  
✅ **Added proper formatting** - Dates are properly formatted using Carbon  
✅ **Added action button** - Includes a "Review Application" button linking to the application  

## Current Email Template

The email now shows:
- Employee name
- Leave type (Annual, Sick, etc.)
- Start and end dates
- Duration in days
- Day type (Full day, Half day)
- Reason for leave
- Action button to review the application

The template is now properly formatted and should display correctly in Mailtrap once SMTP is configured.

