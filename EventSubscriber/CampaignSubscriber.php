<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAmazonSesBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\LeadBundle\Entity\DoNotContact;
use MauticPlugin\MauticAmazonSesBundle\SesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD          => ['onCampaignBuild', 0],
            SesEvents::ON_CAMPAIGN_SES_BOUNCE_CHECK    => ['onBounceCheck', 0],
            SesEvents::ON_CAMPAIGN_SES_COMPLAINT_CHECK => ['onComplaintCheck', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addCondition('ses.bounce_check', [
            'label'       => 'plugin.amazonses.campaign.condition.bounced',
            'description' => 'plugin.amazonses.campaign.condition.bounced.descr',
            'eventName'   => SesEvents::ON_CAMPAIGN_SES_BOUNCE_CHECK,
        ]);

        $event->addCondition('ses.complaint_check', [
            'label'       => 'plugin.amazonses.campaign.condition.complained',
            'description' => 'plugin.amazonses.campaign.condition.complained.descr',
            'eventName'   => SesEvents::ON_CAMPAIGN_SES_COMPLAINT_CHECK,
        ]);
    }

    public function onBounceCheck(CampaignExecutionEvent $event): void
    {
        $lead = $event->getLead();
        $event->setResult(
            $this->hasSesDoNotContact($lead->getId(), DoNotContact::BOUNCED, 'SES Bounce%')
        );
    }

    public function onComplaintCheck(CampaignExecutionEvent $event): void
    {
        $lead = $event->getLead();
        $event->setResult(
            $this->hasSesDoNotContact($lead->getId(), DoNotContact::UNSUBSCRIBED, 'SES Complaint%')
        );
    }

    private function hasSesDoNotContact(int $leadId, int $reason, string $commentPattern): bool
    {
        $tablePrefix = MAUTIC_TABLE_PREFIX;
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM {$tablePrefix}lead_donotcontact WHERE lead_id = ? AND channel = ? AND reason = ? AND comments LIKE ?",
            [$leadId, 'email', $reason, $commentPattern]
        );

        return $count > 0;
    }
}
