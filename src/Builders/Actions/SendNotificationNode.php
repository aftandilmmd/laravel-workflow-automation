<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

class SendNotificationNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'send_notification';
    }

    /**
     * @param  class-string  $notificationClass
     */
    public function notificationClass(string $notificationClass): static
    {
        return $this->set('notification_class', $notificationClass);
    }

    /**
     * @param  class-string  $notifiableClass
     */
    public function notifiableClass(string $notifiableClass): static
    {
        return $this->set('notifiable_class', $notifiableClass);
    }

    public function notifiableId(string $id): static
    {
        return $this->set('notifiable_id', $id);
    }
}
