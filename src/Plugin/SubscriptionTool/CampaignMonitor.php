<?php

namespace Drupal\wmsubscription_campaign_monitor\Plugin\SubscriptionTool;

use CS_REST_Subscribers;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wmsubscription\Annotation\SubscriptionTool;
use Drupal\wmsubscription\Exception\EmailInvalidException;
use Drupal\wmsubscription\Exception\EmailRejectedException;
use Drupal\wmsubscription\Exception\EmailUnconfirmedException;
use Drupal\wmsubscription\Exception\SubscriptionException;
use Drupal\wmsubscription\ListInterface;
use Drupal\wmsubscription\PayloadInterface;
use Drupal\wmsubscription\SubscriptionToolBase;
use Drupal\wmsubscription_campaign_monitor\Subscriber;
use Drupal\wmsubscription_campaign_monitor\SubscriptionList;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SubscriptionTool(
 *     id = "campaign_monitor"
 * )
 */
class CampaignMonitor extends SubscriptionToolBase implements ContainerFactoryPluginInterface
{
    /** @var string */
    protected $apiKey;

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $pluginId,
        $pluginDefinition
    ) {
        $instance = new static($configuration, $pluginId, $pluginDefinition);
        $instance->apiKey = $container->getParameter('wmsubscription_campaign_monitor.api_key');

        return $instance;
    }

    public function addSubscriber(ListInterface $list, PayloadInterface $payload, string $operation = self::OPERATION_CREATE_OR_UPDATE): void
    {
        /** @var SubscriptionList $list */
        /** @var Subscriber $payload */
        $this->validateArguments($list, $payload);

        $client = new CS_REST_Subscribers(
            $list->getId(),
            ['api_key' => $this->apiKey]
        );

        $data = [
            'EmailAddress' => $payload->getEmail(),
            'Resubscribe' => true,
            'ConsentToTrack' => 'Unchanged',
            'CustomFields' => $payload->getCustomFields(),
            'Name' => $payload->getName(),
        ];

        if ($this->isSubscribed($list, $payload)) {
            $result = $client->update($payload->getEmail(), $data);
        } else {
            $result = $client->add($data);
        }

        if (!$result->was_successful()) {
            if ($result->response->Code === 208) {
                throw new EmailUnconfirmedException($result->response->Message);
            }

            if ($result->response->Code === 1) {
                throw new EmailInvalidException($result->response->Message);
            }

            if (in_array($result->response->Code, [204, 205, 206, 207], true)) {
                throw new EmailRejectedException($result->response->Message);
            }

            throw new SubscriptionException($result->response->Message);
        }
    }

    public function getSubscriber(ListInterface $list, PayloadInterface $payload): ?PayloadInterface
    {
        /** @var SubscriptionList $list */
        /** @var Subscriber $payload */
        $this->validateArguments($list, $payload);

        $subscribers = new CS_REST_Subscribers(
            $list->getId(),
            ['api_key' => $this->apiKey]
        );

        $result = $subscribers->get($payload->getEmail(), true);

        if ($result->was_successful()) {
            return new Subscriber(
                $result->response->EmailAddress,
                $result->response->Name,
                $result->response->CustomFields,
                $result->response->State
            );
        }

        return null;
    }

    public function isSubscribed(ListInterface $list, PayloadInterface $payload): bool
    {
        /** @var SubscriptionList $list */
        /** @var Subscriber $payload */
        $this->validateArguments($list, $payload);

        $client = new CS_REST_Subscribers(
            $list->getId(),
            ['api_key' => $this->apiKey]
        );

        $result = $client->get($payload->getEmail());

        return $result->was_successful()
            && $result->response->State === 'Active';
    }

    public function isUpdatable(ListInterface $list, PayloadInterface $payload): bool
    {
        return $this->getSubscriber($list, $payload) !== null;
    }

    protected function validateArguments(ListInterface $list, PayloadInterface $payload)
    {
        if (!$list instanceof SubscriptionList) {
            throw new RuntimeException(
                sprintf('%s is not an instance of Drupal\wmsubscription_campaign_monitor\SubscriptionList!', get_class($list))
            );
        }

        if (!$payload instanceof Subscriber) {
            throw new RuntimeException(
                sprintf('%s is not an instance of Drupal\wmsubscription_campaign_monitor\Subscriber!', get_class($payload))
            );
        }
    }
}
