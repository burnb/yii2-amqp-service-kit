<?php
/**
 * Created by PhpStorm.
 * User: Burn
 */
namespace burn\amqpServiceKit\log\targets;

use burn\amqpServiceKit\log\models\LogError;
use Maknz\Slack\Client;
use yii\log\Logger;
use yii\log\Target;

/**
 * Class SlackTarget - log target class for Slack
 *
 * @package common\components
 */
class SlackTarget extends Target
{
    public $username;
    public $channel;
    public $icon;
    public $endpoint;
    public $viewErrorUrl;

    /**
     * @var $client Client
     */
    private $client;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (!$this->endpoint) {
            throw new \Exception("Slack log target endpoint can`t be empty");
        }
        $settings = ['username' => $this->username];
        if ($this->channel) {
            $settings['channel'] = $this->channel;
        }
        if ($this->icon) {
            $settings['icon'] = $this->icon;
        }
        $this->client = new Client($this->endpoint, $settings);
    }

    /**
     * Send message to Slack channel
     *
     * @inheritdoc
     */
    public function export()
    {
        /** @var LogError $lastErrorLog */
        $lastErrorLog = LogError::find()->where(['level' => LogError::getLevelList($this->levels)])->orderBy(['id' => SORT_DESC])->limit(1)->one();
        $this
            ->client
            ->attach([
                'color'      => 'danger',
                'pretext'    => 'Level: ' . Logger::getLevelName($lastErrorLog->level),
                "title"      => "Log #" . $lastErrorLog->id . ": See full log.",
                "title_link" => \Yii::getAlias($this->viewErrorUrl . '?id=') . $lastErrorLog->id,
                'text'       => 'Category: ' . $lastErrorLog->category
            ])
            ->send();
    }
}