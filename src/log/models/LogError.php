<?php
/**
 * LogError log dbTarget class file.
 */

namespace burn\amqpServiceKit\log\models;

use yii\db\ActiveRecord;
use yii\log\Logger;

/**
 * This is the model class for table "log_error".
 *
 * @property integer $id
 * @property integer $level
 * @property string  $category
 * @property integer $log_time
 * @property string  $prefix
 * @property string  $message
 * @property string  $levelName
 * @property string  $appMessage
 * @property boolean $active Whether error is fixed.
 */
class LogError extends ActiveRecord
{
    const EVENT_IMPORTANT_WARNING = 'importantWarning';
    const EVENT_IMPORTANT_ERROR = 'importantError';

    const DEFAULT_CATEGORY = 'application';
    const DEFAULT_INFO_CATEGORY = 'debug';


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'log_error';
    }

    public static function levelList()
    {
        return [
            Logger::LEVEL_ERROR         => 'ERROR',
            Logger::LEVEL_WARNING       => 'WARNING',
            Logger::LEVEL_INFO          => 'INFO',
            Logger::LEVEL_TRACE         => 'TRACE',
            Logger::LEVEL_PROFILE       => 'PROFILE',
            Logger::LEVEL_PROFILE_BEGIN => 'PROFILE_BEGIN',
            Logger::LEVEL_PROFILE_END   => 'PROFILE_END',
        ];
    }

    /**
     * @param int $bitwiseLevels
     * @return array
     */
    public static function getLevelList($bitwiseLevels = 0)
    {
        $levelList = [];
        foreach (array_keys(self::levelList()) as $level) {
            if ($bitwiseLevels & $level) {
                $levelList[] = (int)$level;
            }
        }

        return $levelList;
    }
}
