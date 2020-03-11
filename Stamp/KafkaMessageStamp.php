<?php declare(strict_types=1);

namespace Easir\KafkaMessengerBundle\Stamp;

use RdKafka\Message;
use Symfony\Component\Messenger\Stamp\StampInterface;

final class KafkaMessageStamp implements StampInterface
{
    /** @var Message */
    private $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
