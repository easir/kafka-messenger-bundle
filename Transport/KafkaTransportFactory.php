<?php declare(strict_types = 1);

namespace Easir\KafkaMessengerBundle\Transport;

use Exception;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use const RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS;
use const RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS;

class KafkaTransportFactory implements TransportFactoryInterface
{
    private const DSN_PROTOCOLS = [
        self::DSN_PROTOCOL_KAFKA,
        self::DSN_PROTOCOL_KAFKA_SSL,
    ];
    private const DSN_PROTOCOL_KAFKA = 'kafka://';
    private const DSN_PROTOCOL_KAFKA_SSL = 'kafka+ssl://';

    /**
     * @param array|mixed[] $options
     */
    public function supports(string $dsn, array $options): bool
    {
        foreach (self::DSN_PROTOCOLS as $protocol) {
            if (strpos($dsn, $protocol) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array|mixed[] $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $conf = new Conf();
        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(static function (KafkaConsumer $kafka, $err, ?array $topicPartitions = null): void {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($topicPartitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(null);
                    break;

                default:
                    throw new Exception($err);
            }
        });
        $brokers = $this->stripProtocol($dsn);
        $conf->set('metadata.broker.list', implode(',', $brokers));
        if (!empty($options['group_id'])) {
            $conf->set('group.id', (string) $options['group_id']);
        }
        if (!empty($options['conf'])) {
            foreach ($options['conf'] as $key => $value) {
                $conf->set($key, $value);
            }
        }

        return new KafkaTransport(
            $serializer,
            $conf,
            $options['topics'],
            (int) ($options['receiveTimeout'] ?? 10000),
            (bool) ($options['commitAsync'] ?? true)
        );
    }

    /**
     * @return array|string[]
     */
    private function stripProtocol(string $dsn): array
    {
        $brokers = [];
        foreach (explode(',', $dsn) as $currentBroker) {
            foreach (self::DSN_PROTOCOLS as $protocol) {
                $currentBroker = str_replace($protocol, '', $currentBroker);
            }
            $brokers[] = $currentBroker;
        }

        return $brokers;
    }
}
