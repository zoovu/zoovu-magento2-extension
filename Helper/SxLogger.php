<?php namespace Semknox\Productsearch\Helper;

use Semknox\Core\Services\Logging\NullLoggingService;

if (class_exists('\Laminas\Log\Writer\Stream')) {
    // Magento >= 2.3.5
    class SemknoxWriterStreamBridge extends \Laminas\Log\Writer\Stream
    {
    }
} else {
    class SemknoxWriterStreamBridge extends \Zend\Log\Writer\Stream
    {
    }
}

if (class_exists('\Laminas\Log\Logger')) {
    // Magento >= 2.3.5
    class SemknoxLoggerBridge extends \Laminas\Log\Logger
    {
    }
} else {
    class SemknoxLoggerBridge extends \Zend\Log\Logger
    {
    }
}



class SxLogger extends NullLoggingService {

    /**
     * @inheritDoc
     */
    public function info($message)
    {
        $this->log($message,'info');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function warning($message)
    {
        $this->log($message,'warning');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function error($message)
    {
        $this->log($message,'error');
        return true;
    }


    public function log($message, $logLevel = 'info')
    {

        $writer = new SemknoxWriterStreamBridge(BP . '/var/log/semknox.log');
        $logger = new SemknoxLoggerBridge();
        $logger->addWriter($writer);

        $logLevel = \strtolower($logLevel);
        switch($logLevel){
            case 'error':
                $logLevel = 'err';
                break;
            case 'warning':
                $logLevel = 'warn';
                break;
            case 'debug':
                $logLevel = 'debug';
                break;
            default:
                if (!in_array($logLevel, ['info', 'alert', 'notice'])) {
                    $logLevel = 'info';
                }
                break;

        }

        $logger->$logLevel($message);
    }

}