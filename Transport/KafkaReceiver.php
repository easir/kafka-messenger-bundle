<?php declare(strict_types=1);

namespace Easir\KafkaMessengerBundle\Transport;

use Easir\KafkaMessengerBundle\Stamp\KafkaMessageStamp;
use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Throwable;
use const RD_KAFKA_RESP_ERR__PARTITION_EOF;
use const RD_KAFKA_RESP_ERR__TIMED_OUT;
use const RD_KAFKA_RESP_ERR_INVALID_SESSION_TIMEOUT;

class KafkaReceiver implements ReceiverInterface
{
    private const IGNORED_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
        RD_KAFKA_RESP_ERR_INVALID_SESSION_TIMEOUT,
    ];

    /** @var SerializerInterface */
    private $serializer;
    /** @var KafkaConsumer */
    private $consumer;
    /** @var bool */
    private $commitAsync;
    /** @var array|string[] */
    private $topicNames;
    /** @var int in ms */
    private $timeout;

    /**
     * @param array|string[] $topicNames
     */
    public function __construct(
        SerializerInterface $serializer,
        Conf $configuration,
        array $topicNames,
        bool $commitAsync = true,
        int $timeout = 10000
    ) {
        $this->serializer = $serializer;
        $this->consumer = new KafkaConsumer($configuration);
        $this->topicNames = $topicNames;
        $this->commitAsync = $commitAsync;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        static $subscribed = false;

        try {
            if ($subscribed === false) {
                $this->consumer->subscribe(array_keys($this->topicNames));
                $subscribed = true;
            }
            $message = $this->consumer->consume($this->timeout);
            if ($message->err) {
                if (in_array($message->err, self::IGNORED_ERRORS)) {
                    return [];
                }
                throw new TransportException($message->errstr(), $message->err);
            }
            $envelope = $this->serializer->decode([
                'body' => $message->payload,
                'headers' => [
                    'type' => $this->topicNames[$message->topic_name],
                    'timestamp' => $message->timestamp,
                ],
            ]);

            if ($envelope) {
                $envelope = $envelope->with(new KafkaMessageStamp($message));
            }

            return [$envelope];
        } catch (Throwable $exception) {
            throw new TransportException(
                'Consume failed on message deserialization, see previous exception',
                0,
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function ack(Envelope $envelope): void
    {
        /** @var KafkaMessageStamp $transportStamp */
        $transportStamp = $envelope->last(KafkaMessageStamp::class);
        $message = $transportStamp->getMessage();

        try {
            if ($this->commitAsync) {
                $this->consumer->commitAsync($message);
                return;
            }
            $this->consumer->commit($message);
        } catch (Exception $exception) {
            throw new TransportException(
                sprintf(
                    'Ack of message from topic %s at offset %d failed',
                    $message->topic_name,
                    $message->offset
                ),
                0,
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function reject(Envelope $envelope): void
    {
        // Do nothing. auto commit should be set to false!
    }
}
