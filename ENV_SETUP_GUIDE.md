# 🔐 Environment Variables Setup Guide

This guide explains how to securely configure environment variables for the Budgetly application.

## 🚀 Quick Setup

### 1. Copy Environment Template
```bash
cp .env.example .env
```

### 2. Edit .env File
Open `.env` and replace the template values with your actual credentials:

```bash
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_actual_google_client_id_from_console
GOOGLE_CLIENT_SECRET=your_actual_google_client_secret_from_console

# Application Settings
APP_ENV=development
APP_DEBUG=true
```

## 🔒 Security Features

### Files Protected by .gitignore
- `.env` - Your actual environment variables
- `.env.local` - Local overrides
- `.env.production` - Production settings
- `config/secrets.php` - Any other secret files

### Environment Variable Loader
The `config/env_loader.php` automatically:
- Loads variables from `.env` file
- Provides `env()` helper function
- Supports default values
- Works across different PHP environments

## 🌍 Environment Configurations

### Development (.env)
```bash
APP_ENV=development
APP_DEBUG=true
GOOGLE_CLIENT_ID=your_dev_client_id
GOOGLE_CLIENT_SECRET=your_dev_client_secret
```

### Production
Set environment variables directly on your server or use `.env.production`:
```bash
APP_ENV=production
APP_DEBUG=false
GOOGLE_CLIENT_ID=your_prod_client_id
GOOGLE_CLIENT_SECRET=your_prod_client_secret
```

## 🔧 Usage in Code

### Basic Usage
```php
// Load environment variables
require_once 'config/env_loader.php';

// Get variables with defaults
$clientId = env('GOOGLE_CLIENT_ID', 'default_value');
$debug = env('APP_DEBUG', false);
```

### In Configuration Files
```php
define('GOOGLE_OAUTH_CONFIG', [
    'client_id' => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    // ... other config
]);
```

## ⚠️ Important Security Notes

1. **Never commit .env to Git** - It's in .gitignore for a reason
2. **Use .env.example for templates** - Share this instead of .env
3. **Rotate secrets regularly** - Change OAuth credentials periodically
4. **Use different credentials per environment** - Dev, staging, and production should have separate OAuth apps

## 🐛 Troubleshooting

### "OAuth not configured" Error
- Check if `.env` file exists
- Verify `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are set
- Ensure values are not the template placeholders

### Environment Variables Not Loading
- Check file path in `env_loader.php`
- Verify `.env` file permissions
- Check for syntax errors in `.env` file

### OAuth Errors
- Verify credentials in Google Cloud Console
- Check redirect URIs match exactly
- Ensure OAuth is enabled in database settings

## 📋 Deployment Checklist

- [ ] Copy `.env.example` to `.env`
- [ ] Fill in real OAuth credentials
- [ ] Set appropriate `APP_ENV` (development/production)
- [ ] Verify `.env` is in `.gitignore`
- [ ] Test OAuth configuration
- [ ] Run database migrations if needed

## 🔄 Updating Credentials

### For Google OAuth:
1. Go to Google Cloud Console
2. Generate new OAuth credentials
3. Update `.env` file
4. Restart web server if needed
5. Test login functionality

### For Database:
1. Update `.env` with new DB credentials
2. Test database connection
3. Verify all features work correctly
