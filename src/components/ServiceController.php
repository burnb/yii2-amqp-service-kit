<?php
declare(ticks = 100);

namespace burn\amqpServiceKit\components;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use yii\console\Controller;

/**
 * Class ServiceController
 *
 * @package app\components
 */
class ServiceController extends Controller
{
    const MAX_ATTEMPT_COUNT = 10;
    const DELAY_BEFORE_REQUEUE_SECONDS = 10 * 60;
    const SLEEP_BEFORE_RECONNECT_SECONDS = 10;

    public $defaultAction = 'main';
    public $serviceName;

    public $rabbitIP = 'localhost';
    public $rabbitPort = 5672;
    public $rabbitUser = 'guest';
    public $rabbitPassword = 'guest';

    public $routingKey;
    public $exchangeName;
    public $queueName;

    public $deadLetterExchangeName = "dlx";
    public $deadLetterQueueName = 'dead';
    public $delayedQueueName = 'delayed';
    public $delayedExchangeName = 'delayed';

    protected $consumed = 0;
    /**
     * @var AMQPChannel
     */
    protected $channel;
    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $rabbitConfig = \Yii::$app->params['rabbit'];
        $this->rabbitIP = $rabbitConfig['host'] ?: $this->rabbitIP;
        $this->rabbitPort = $rabbitConfig['port'] ?: $this->rabbitPort;
        $this->rabbitUser = $rabbitConfig['username'] ?: $this->rabbitUser;
        $this->rabbitPassword = $rabbitConfig['password'] ?: $this->rabbitPassword;
    }

    /**
     * Start worker for processing action
     */
    public function actionMain()
    {
        $this->initPcntlSignalHandlers();
        $this->initAmqp();
        while (count($this->channel->callbacks)) {
            printf("\r\n\033[1;31m MEMORY USAGE: " . memory_get_usage(true) / 1024 / 1024 . "Mb \033[m\r\n");
            printf("Consumed: $this->consumed  \r\n");
            $this->channel->wait();
        }
    }

    protected function initPcntlSignalHandlers()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            pcntl_signal(SIGTERM, [$this, 'pcntlHandler']);
            pcntl_signal(SIGINT, [$this, 'pcntlHandler']);
        }
    }

    /**
     * @param $signal
     */
    public function pcntlHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
                $this->finalize();
                break;
            case SIGINT:
                $this->finalize();
                break;
            default:
                // do nothing
        }
    }

    /**
     * Init queue connection
     */
    protected function initAmqp()
    {
        do {
            try {
                printf("TRY CONNECT TO QUEUE...\r\n");
                $this->connection = new AMQPStreamConnection($this->rabbitIP, $this->rabbitPort, $this->rabbitUser, $this->rabbitPassword);
                $again = false;
            } catch (\Exception $e) {
                $sleepTime = self::SLEEP_BEFORE_RECONNECT_SECONDS;
                printf("FAIL TO CONNECT. SLEEP $sleepTime SECONDS\r\n");
                sleep($sleepTime);
                $again = true;
            }
        } while ($again);
        printf("CONNECTED TO QUEUE\r\n");
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($this->deadLetterExchangeName, 'direct', false, true, false);
        $this->channel->queue_declare($this->deadLetterQueueName, false, true, false, false, false);
        $this->channel->queue_bind($this->deadLetterQueueName, $this->deadLetterExchangeName, $this->routingKey);
        $this->channel->exchange_declare($this->delayedExchangeName, 'direct', false, true, false);
        $this->channel->queue_declare($this->delayedQueueName, false, true, false, false, false, new AMQPTable(["x-dead-letter-exchange" => $this->exchangeName, "x-message-ttl" => self::DELAY_BEFORE_REQUEUE_SECONDS * 1000]));
        $this->channel->queue_bind($this->delayedQueueName, $this->delayedExchangeName, $this->routingKey);
        $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);
        $this->channel->queue_declare($this->queueName, false, true, false, false, false, new AMQPTable(["x-dead-letter-exchange" => $this->delayedExchangeName]));
        $this->channel->queue_bind($this->queueName, $this->exchangeName, $this->routingKey);
        $this->channel->basic_qos(NULL, 1, NULL);
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, [$this, 'process']);
    }

    /**
     * Base process action
     *
     * @param AMQPMessage $msg
     * @return bool
     */
    public function process($msg)
    {
        self::pingDbConnection();
        printf("\r\nProcess: $msg->body");
        if (isset($msg->get_properties()['application_headers'])) {
            /** @var AMQPTable $headers */
            $headers = $msg->get('application_headers');
            $count = isset($headers->getNativeData()['x-death'][0]['count']) ? $headers->getNativeData()['x-death'][0]['count'] : 0;
            if ($count > self::MAX_ATTEMPT_COUNT) {
                $msg->delivery_info['channel']->basic_publish($msg, $this->deadLetterExchangeName, $this->routingKey);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                printf("\r\n" . __CLASS__ . " MAX ATTEMPT COUNT! MESSAGE $msg->body MOVED TO DEAD LETTER EXCHANGE");
                \Yii::e("SERVICE: {$this->serviceName} | MAX ATTEMPT COUNT! MESSAGE $msg->body MOVED TO DEAD LETTER EXCHANGE");

                return false;
            }
        }

        return true;
    }

    /**
     * Check db connection and if it down reopen
     */
    public static function pingDbConnection()
    {
        try {
            if (\Yii::$app->getDb()->pdo) {
                \Yii::$app->getDb()->pdo->query('SELECT 1');
            }
        } catch (\Exception $e) {
            \Yii::$app->getDb()->close();
            \Yii::$app->getDb()->open();
        }
    }

    /**
     * Graceful finalize
     */
    protected function finalize()
    {
        exit;
    }
}