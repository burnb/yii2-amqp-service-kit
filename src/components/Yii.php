<?php

use burn\amqpServiceKit\log\models\LogError;

defined('YII_DEBUG') or define('YII_DEBUG', env('YII_DEBUG', false));
defined('YII_ENV') or define('YII_ENV', env('YII_ENV'));

require(__DIR__ . '/../../../../yiisoft/yii2/BaseYii.php');

/**
 * Yii is a helper class serving common framework functionalities.
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 */
class Yii extends \yii\BaseYii
{
    /**
     * Logs an error message.
     *
     * @param string $message  the message to be logged.
     * @param bool   $isImportant
     * @param string $category the category of the message.
     */
    public static function e($message, $isImportant = false, $category = NULL)
    {
        list($message, $category) = self::formatCategoryMessage($message, $category, $isImportant, __METHOD__);
        static::error($message, $category);
        if ($isImportant) {
            static::$app->trigger(LogError::EVENT_IMPORTANT_ERROR);
        }
    }

    /**
     * Logs an error message by catch exception.
     *
     * @param Exception $e
     * @param bool      $isImportant
     * @param string    $message  the message to be logged.
     * @param string    $category the category of the message.
     */
    public static function ex(Exception $e, $message = NULL, $isImportant = false, $category = NULL)
    {
        try {
            $exceptionMessage = "{$e->getMessage()} \r\nTRACE: {$e->getTraceAsString()}";
            $message = $message ? "$message $exceptionMessage" : $exceptionMessage;
            list($message, $category) = self::formatCategoryMessage($message, $category, $isImportant, __METHOD__);
            static::error($message, $category);
            if ($isImportant) {
                static::$app->trigger(LogError::EVENT_IMPORTANT_ERROR);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Logs a warning message.
     *
     * @param string $message  the message to be logged.
     * @param bool   $isImportant
     * @param string $category the category of the message.
     */
    public static function w($message, $isImportant = false, $category = NULL)
    {
        list($message, $category) = self::formatCategoryMessage($message, $category, $isImportant, __METHOD__);
        static::warning($message, $category);
        if ($isImportant) {
            static::$app->trigger(LogError::EVENT_IMPORTANT_WARNING);
        }
    }

    /**
     * Logs a info message.
     *
     * @param string $message  the message to be logged.
     * @param string $category the category of the message.
     */
    public static function i($message, $category = NULL)
    {
        list($message, $category) = self::formatCategoryMessage($message, $category, false, __METHOD__);
        static::info($message, $category);
    }

    /**
     * Logs an info message by catch exception.
     *
     * @param Exception $e
     * @param string    $message  the message to be logged.
     * @param string    $category the category of the message.
     */
    public static function ix(Exception $e, $message = NULL, $category = NULL)
    {
        try {
            $exceptionMessage = "{$e->getMessage()} \r\nTRACE: {$e->getTraceAsString()}";
            $message = $message ? "$message $exceptionMessage" : $exceptionMessage;
            list($message, $category) = self::formatCategoryMessage($message, $category, false, __METHOD__);
            static::info($message, $category);
        } catch (Exception $e) {
        }
    }

    /**
     * Logs message.
     *
     * @param string $message  the message to be logged.
     * @param string $category the category of the message.
     */
    public static function log($message, $category = NULL)
    {
        static::info($message, self::formatCategory($category, __METHOD__));
    }

    /**
     * Get needed trace line form app backtrace for logging
     *
     * @param string $method __METHOD__
     * @return array|NULL
     */
    private static function getErrorTraceLine($method)
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
        foreach ($backTrace as $index => $traceLine) {
            if (
                isset($traceLine['class']) &&
                isset($traceLine['type']) &&
                isset($traceLine['function']) &&
                $traceLine['class'] . $traceLine['type'] . $traceLine['function'] == $method
            ) {
                return $backTrace[++$index];
            }
        }

        return NULL;
    }

    /**
     * Format category and message for logging with backtrace info
     *
     * @param $message
     * @param $category
     * @param $isImportant
     * @param $method
     * @return array
     */
    private static function formatCategoryMessage($message, $category, $isImportant, $method)
    {
        if ($errorTraceLine = self::getErrorTraceLine($method)) {
            $message =
                "ERROR MESSAGE: " . $message .
                (!empty($errorTraceLine['line']) ? " \r\nLINE: " . $errorTraceLine['line'] : "") .
                (!empty($errorTraceLine['args']) ? " \r\nARGUMENTS: " . json_encode($errorTraceLine['args']) : "");
            $category = $category ?: @$errorTraceLine['class'] . @$errorTraceLine['type'] . @$errorTraceLine['function'] . "()";
        }

        return [$message, ($isImportant ? "(!)" : "") . ($category ?: LogError::DEFAULT_CATEGORY)];
    }

    /**
     * Format category for logging with backtrace info
     *
     * @param $category
     * @param $method
     * @return array
     */
    private static function formatCategory($category, $method)
    {
        if ($errorTraceLine = self::getErrorTraceLine($method)) {
            $category = $category ?: @$errorTraceLine['class'] . @$errorTraceLine['type'] . @$errorTraceLine['function'];
        }

        return $category ?: LogError::DEFAULT_CATEGORY;
    }
}

spl_autoload_register(['Yii', 'autoload'], true, true);
Yii::$classMap = require(__DIR__ . '/../../../../yiisoft/yii2/classes.php');
Yii::$container = new yii\di\Container();
