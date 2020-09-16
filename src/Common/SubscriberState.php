<?php

namespace Drupal\wmsubscription_campaign_monitor\Common;

/**
 * @see https://www.campaignmonitor.com/api/subscribers#getting-subscribers-details
 */
class SubscriberState
{
    public const ACTIVE = 'Active';
    public const UNCONFIRMED = 'Unconfirmed';
    public const UNSUBSCRIBED = 'Unsubscribed';
    public const BOUNCED = 'Bounced';
    public const DELETED = 'Deleted';

    public static function getLabels(): array
    {
        return [
            self::ACTIVE => t('Active'),
            self::UNCONFIRMED => t('Unconfirmed'),
            self::UNSUBSCRIBED => t('Unsubscribed'),
            self::BOUNCED => t('Bounced'),
            self::DELETED => t('Deleted'),
        ];
    }
}
