<?php
namespace Hhxsv5\LaravelS\Illuminate\Database\Connectors;

use Hhxsv5\LaravelS\Illuminate\Pool\MysqlPool;
use Illuminate\Support\Arr;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Hhxsv5\LaravelS\Illuminate\Database\SwoolePDO;
use Illuminate\Support\Str;
use Exception;

class CoroutineMySQLConnector extends Connector implements ConnectorInterface
{
    /**
     * @param string $dsn
     * @param array  $config
     * @param array  $options
     * @return SwoolePDO
     * @throws \Throwable
     */
    public function createConnection ($dsn, array $config, array $options)
    {
        try {
            $mysql = $this->connect($config);
        } catch (\Exception $e) {
            $mysql = $this->_tryAgainIfCausedByLostConnection($e, $config);
        }
        return $mysql;
    }

    /**
     * @param \Throwable $e
     * @param array      $config
     * @return SwoolePDO
     * @throws \Throwable
     */
    protected function _tryAgainIfCausedByLostConnection ($e, array $config)
    {
        if ($this->causedByLostConnection($e)) {
            return $this->connect($config);
        }
        throw $e;
    }

    /**
     * @param array $config
     * @return SwoolePDO
     */
    public function connect (array $config)
    {
        $connection = new SwoolePDO();
        //        return $connection->get();
        $connection->connect([
            'host' => Arr::get($config, 'host', '127.0.0.1'),
            'port' => Arr::get($config, 'port', 3306),
            'user' => Arr::get($config, 'username', 'hhxsv5'),
            'password' => Arr::get($config, 'password', '52100'),
            'database' => Arr::get($config, 'database', 'test'),
            'timeout' => Arr::get($config, 'timeout', 5),
            'charset' => Arr::get($config, 'charset', 'utf8mb4'),
            'strict_type' => Arr::get($config, 'strict', false),
        ]);
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
        if (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare("set session sql_mode='STRICT_ALL_TABLES,ANSI_QUOTES'")->execute();
            } else {
                $connection->prepare("set session sql_mode='ANSI_QUOTES'")->execute();
            }
        }
        return $connection;
    }

    /**
     * 重写重连异常信息
     * @param \Exception $e
     * @return bool
     */
    protected function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            "The MySQL connection is not established",
            "is closed",
        ]);
    }
}
