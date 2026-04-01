# Mautic Amazon SES Plugin

[![Latest Version](https://img.shields.io/packagist/v/iamjpsingh/mautic-ses-plugin.svg)](https://packagist.org/packages/iamjpsingh/mautic-ses-plugin)
[![License](https://img.shields.io/packagist/l/iamjpsingh/mautic-ses-plugin.svg)](https://packagist.org/packages/iamjpsingh/mautic-ses-plugin)
[![PHP Version](https://img.shields.io/packagist/php-v/iamjpsingh/mautic-ses-plugin.svg)](https://packagist.org/packages/iamjpsingh/mautic-ses-plugin)

Amazon SES webhook handler plugin for **Mautic 6+**. Automatically tracks bounces, complaints, deliveries, and more via AWS SNS webhooks. Marks contacts as **Do Not Contact** on hard bounces and spam complaints.

Works with Symfony's built-in SES transport (`symfony/amazon-mailer`) — no custom transport needed.

## Credits

This plugin is based on the amazing work by **[@pm-pmaas](https://github.com/pm-pmaas)** and their original [etailors/mautic-amazon-ses](https://github.com/pm-pmaas/etailors_amazon_ses) plugin. Huge thanks to them for building the foundation!

The original plugin supports Mautic 4/5. This version has been **rewritten and upgraded** for Mautic 6+ with:
- Full support for **all 10 SES event types** (not just bounce/complaint/delivery)
- **SES v1 + v2** payload format support
- Email address parsing fix for SES `"Display Name" <email>` format
- SNS `UnsubscribeConfirmation` handling
- Modern PHP 8.2+ strict typing
- **Composer/Packagist** ready for easy installation

---

## Requirements

- Mautic 6.x / 7.x+
- PHP 8.2+
- `symfony/amazon-mailer` package
- AWS account with SES and SNS access
- SES verified domain or email address

## Installation

```bash
composer require iamjpsingh/mautic-ses-plugin
php bin/console cache:clear
php bin/console mautic:plugins:reload
```

That's it! The plugin is ready to use.

## Quick Start Guide

### Step 1: Create IAM Access Keys

1. Go to **AWS Console > IAM > Users**
2. Select or create a user with SES permissions
3. Go to **Security credentials > Create access key**
4. Save the **Access Key ID** and **Secret Access Key**


### Step 2: Configure Mautic Email Settings

Go to **Mautic > Settings > Email Settings** and configure:

| Field | Value |
|---|---|
| **Scheme** | `ses+api` |
| **Host** | `default` |
| **User** | Your AWS Access Key ID |
| **Password** | Your AWS Secret Access Key |
| **Options** | Key: `region`, Value: your AWS region (e.g. `us-east-1`, `ap-south-1`) |

![alt text](api-setup.png)

Click **Save** and **Send Test Email** to verify.

### Step 3: Create SNS Topic

1. Go to **AWS SNS Console > Topics > Create topic**
2. Select **Standard** type
3. Name it `mautic-ses-notifications`
4. Click **Create topic**


### Step 4: Create SNS Subscription

1. Inside the topic, click **Create subscription**
2. Set:
   - **Protocol**: HTTPS
   - **Endpoint**: `https://your-domain.com/mailer/callback`
3. Click **Create subscription**
4. The plugin **auto-confirms** the subscription


### Step 5: Connect SES to SNS

1. Go to **SES > Identities > your-domain > Notifications tab**
2. Click **Edit** on **Feedback notifications**
3. Set your SNS topic for **Bounce**, **Complaint**, and **Delivery**
4. Enable **Include original email headers**
5. Click **Save**


### Step 6: Test It!

1. Create a contact in Mautic with email: `bounce@simulator.amazonses.com`
2. Send an email to that contact
3. The contact should be marked as **Do Not Contact (Bounced)**

![alt text](testing.png
)

## Alternative Configuration Methods

### Via config/local.php

```php
'mailer_dsn' => 'ses+api://ACCESS_KEY:SECRET_KEY@default?region=us-east-1',
```

### Via Environment Variable

```bash
MAILER_DSN=ses+api://ACCESS_KEY:SECRET_KEY@default?region=us-east-1
```

## Available DSN Schemes

| Scheme | Protocol | Use case |
|---|---|---|
| `ses+api` | SES v2 HTTPS API | **Recommended** - fastest |
| `ses+https` | SES v2 HTTPS | Alternative API method |
| `ses+smtp` | SMTP via SES | Use if API access is restricted |
| `ses` | Default (same as `ses+smtp`) | Fallback |

## Supported SES Event Types

All 10 SES event types are handled:

| Event Type | Action | Description |
|---|---|---|
| **Bounce** | Marks contact as **Do Not Contact (Bounced)** | Hard/soft bounce with full diagnostics |
| **Complaint** | Marks contact as **Do Not Contact (Unsubscribed)** | Spam complaint from recipient |
| **Delivery** | Logged | Successful delivery confirmation |
| **Reject** | Marks contact as **Do Not Contact (Bounced)** | SES rejected the email (e.g., virus detected) |
| **Send** | Logged | SES accepted the email for delivery |
| **Open** | Logged | Recipient opened the email |
| **Click** | Logged | Recipient clicked a link |
| **DeliveryDelay** | Logged as warning | Temporary delay (mailbox full, etc.) |
| **Rendering Failure** | Logged as error | SES template rendering failed |
| **Subscription** | Logged | Recipient changed subscription preferences |

## SNS Message Types

| Type | Action |
|---|---|
| **SubscriptionConfirmation** | Auto-confirmed (calls SubscribeURL) |
| **UnsubscribeConfirmation** | Logged as warning |
| **Notification** | Unwrapped and processed as SES event |

Supports both **SES v1** (`notificationType`) and **SES v2** (`eventType`) payload formats.

## Advanced: Configuration Set (Optional)

For tracking Open, Click, and other v2 events, create a Configuration Set:

1. Go to **SES > Configuration Sets > Create**
2. Name it `mautic-tracking`
3. Add an **SNS event destination** for all event types
4. Add `configuration_set=mautic-tracking` to your DSN options

## Webhook Endpoint

```
POST /mailer/callback
```

Accepts:
- SNS SubscriptionConfirmation (auto-confirmed)
- SNS UnsubscribeConfirmation (acknowledged)
- SNS Notification wrapping SES events
- Direct SES event JSON

## SES Simulator Addresses (For Testing)

| Address | Simulates |
|---|---|
| `bounce@simulator.amazonses.com` | Hard bounce |
| `complaint@simulator.amazonses.com` | Spam complaint |
| `success@simulator.amazonses.com` | Successful delivery |
| `suppressionlist@simulator.amazonses.com` | Suppression list bounce |
| `ooto@simulator.amazonses.com` | Out of office (soft bounce) |

## Troubleshooting

### "Unsupported scheme ses+api" error
```bash
composer require symfony/amazon-mailer
```

### Bounces not being recorded
- Verify SNS subscription is **Confirmed** in AWS SNS Console
- Check SES Notifications tab - is your SNS topic selected for Bounce/Complaint/Delivery?
- Check webhook URL is publicly accessible
- Check Mautic logs: `var/logs/mautic_*.php`

### Test email works but campaign emails don't
- Check SES sending limits in AWS Console
- Verify you're out of the SES sandbox (or recipients are verified)

### Plugin not showing in Mautic
```bash
php bin/console cache:clear
php bin/console mautic:plugins:reload
```

## IAM Policy

Minimum required IAM permissions for sending:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ses:SendEmail",
                "ses:SendRawEmail",
                "ses:GetSendStatistics",
                "ses:GetSendQuota"
            ],
            "Resource": "*"
        }
    ]
}
```

## Contributing

Found a bug? Have a suggestion? Please [open an issue](https://github.com/iamjpsingh/mautic-ses-plugin/issues) or submit a pull request.

## License

GPL-3.0-or-later

---

**Made with love for the Mautic community. Special thanks to [@pm-pmaas](https://github.com/pm-pmaas) for the original [etailors/mautic-amazon-ses](https://github.com/pm-pmaas/etailors_amazon_ses) plugin that made this possible.**
