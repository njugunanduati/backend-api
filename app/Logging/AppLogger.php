<?php

namespace App\Logging;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Monolog\Formatter\JsonFormatter;
use Maxbanton\Cwh\Handler\CloudWatch;


class AppLogger
{
    protected $client, $log;
    function __construct()
    {
        $sdkParams = [
            'region' => env('AWS_REGION', 'us-east-1'),
            'version' => env('CLOUDWATCH_LOG_VERSION', 'latest'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID_CLOUD_WATCH', ''),
                'secret' => env('AWS_SECRET_ACCESS_KEY_CLOUD_WATCH', ''),
            ],
            'http'    => [
                'verify' => false
            ]
        ];
        $this->client = new CloudWatchLogsClient($sdkParams);
        $this->log = new Logger(env('LOG_CHANNEL', 'dummy'));
    }

    /**
     * Application Logs on local server and CloudWatchLogs
     *
     * @param $mode - Log mode:debug|warning|error|recover
     * @param $logInfo - Log message
     * @param $extraMessage - Log context
     *
     */
    public function logRequest($mode, $logInfo, $extraMessage = [])
    {
        $infoHandler = new StreamHandler(base_path() . "/storage/logs/laravel.log", Logger::DEBUG);
        $infoHandler->setFormatter(new JsonFormatter());
        $this->log->pushHandler($infoHandler);

        try {
            $handler = new CloudWatch($this->client, env('CLOUDWATCH_LOG_GROUP', 'dummy'), env('CLOUDWATCH_LOG_STREAM', 'dummy'), env('CLOUDWATCH_LOG_RETENTION', null), 1000,);
        } catch (\Aws\Exception\AwsException $e) {
            $this->log->info($e->getMessage());
        }

        $handler->setFormatter(new JsonFormatter());
        $this->log->pushHandler($handler);

        if (!is_array($extraMessage)) {
            $extraMessage = [$extraMessage];
        }

        // Add records to the log
        switch ($mode) {
            case "debug":
                $this->log->debug($logInfo, $extraMessage);
                break;
            case "warning":
                $this->log->warning($logInfo, $extraMessage);
                break;
            case "error":
                $this->log->error($logInfo, $extraMessage);
                break;
            default:
                $this->log->info($logInfo, $extraMessage);
        }
    }
}