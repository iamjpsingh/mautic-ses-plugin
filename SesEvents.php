<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAmazonSesBundle;

final class SesEvents
{
    /**
     * Dispatched when evaluating the "SES: Contact Bounced" campaign condition.
     */
    public const ON_CAMPAIGN_SES_BOUNCE_CHECK = 'mautic.plugin.ses.campaign.bounce_check';

    /**
     * Dispatched when evaluating the "SES: Contact Complained" campaign condition.
     */
    public const ON_CAMPAIGN_SES_COMPLAINT_CHECK = 'mautic.plugin.ses.campaign.complaint_check';
}
