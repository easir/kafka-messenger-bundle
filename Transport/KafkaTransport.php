<?php declare(strict_types=1);

namespace Easir\KafkaMessengerBundle\Transport;

use RdKafka\Conf;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class KafkaTransport implements TransportInterface
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var Conf */
    private $configuration;
    /** @var array|string[] */
    private $topicNames = [];
    /** @var int */
    private $timeout;
    /** @var bool */
    private $commitAsync;
    /** @var KafkaReceiver */
    private $receiver;

    /**
     * @param array|string[] $topicNames
     */
    public function __construct(
        SerializerInterface $serializer,
        Conf $kafkaConf,
        array $topicNames,
        int $timeoutMs,
        bool $commitAsync
    ) {
        $this->serializer = $serializer;
        $this->configuration = $kafkaConf;
        $this->topicNames = $topicNames;
        $this->timeout = $timeoutMs;
        $this->commitAsync = $commitAsync;
    }

    /**
     * @return iterable|Envelope[]
     */
    public function get(): iterable
    {
        return ($this->receiver ?? $this->getReceiver())->get();
    }

    private function getReceiver(): KafkaReceiver
    {
        return $this->receiver = new KafkaReceiver(
            $this->serializer,
            $this->configuration,
            $this->topicNames,
            $this->commitAsync,
            $this->timeout
        );
    }

    public function ack(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        throw new TransportException(sprintf(
            'Not supported in %s implementation',
            self::class
        ));
    }
}
