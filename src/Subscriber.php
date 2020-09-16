<?php

namespace Drupal\wmsubscription_campaign_monitor;

use Drupal\wmsubscription\PayloadBase;
use Drupal\wmsubscription_campaign_monitor\Common\SubscriberState;

class Subscriber extends PayloadBase
{
    /** @var string */
    protected $name;
    /** @var array */
    protected $customFields;
    /** @var string */
    protected $state;

    public function __construct(
        string $email,
        string $name,
        array $customFields = [],
        ?string $state = null
    ) {
        parent::__construct($email);
        $this->name = $name;
        $this->customFields = $customFields;
        $this->state = $state;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $value): self
    {
        $this->name = $value;
        return $this;
    }

    public function getCustomFields(): array
    {
        $data = [];

        foreach ($this->customFields as $name => $value) {
            if (isset($value['Key'], $value['Value'])) {
                $data[] = $value;
            } else {
                $data[] = [
                    'Key' => $name,
                    'Value' => $value,
                ];
            }
        }

        return $data;
    }

    public function setCustomFields(array $value): self
    {
        $this->customFields = $value;
        return $this;
    }

    public function getState(): string
    {
        return $this->state ?? SubscriberState::ACTIVE;
    }

    public function setState(?string $value): self
    {
        $this->state = $value;
        return $this;
    }
}
