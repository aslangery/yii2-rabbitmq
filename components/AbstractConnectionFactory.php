<?php declare(strict_types=1);

namespace mikemadisonweb\rabbitmq\components;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AbstractConnectionFactory
{
    /** @var \ReflectionClass */
    private $_class;

    /** @var array */
    private $_parameters;

    /**
     * Constructor
     *
     * @param string $class      FQCN of AMQPConnection class to instantiate.
     * @param array  $parameters Map containing parameters resolved by Extension.
     */
    public function __construct($class, array $parameters)
    {
        $this->_class = $class;
        $this->_parameters = $this->parseUrl($parameters);
    }

    /**
     * @return mixed
     */
    public function createConnection() : AbstractConnection
    {
        $amqpConfig = new AMQPConnectionConfig();
        if ($this->_parameters['type'] === AMQPStreamConnection::class) {
            $amqpConfig->setIoType(AMQPConnectionConfig::IO_TYPE_STREAM);
        }
        if ($this->_parameters['type'] === AMQPSocketConnection::class) {
            $amqpConfig->setIoType(AMQPConnectionConfig::IO_TYPE_SOCKET);
        }
        $amqpConfig->setHost($this->_parameters['host']);
        $amqpConfig->setPort($this->_parameters['port']);
        $amqpConfig->setUser($this->_parameters['user']);
        $amqpConfig->setPassword($this->_parameters['password']);
        $amqpConfig->setVhost($this->_parameters['vhost']);
        $amqpConfig->setInsist(false);      // insist
        $amqpConfig->setLoginMethod( 'AMQPLAIN'); // login_method
        $amqpConfig->setLoginResponse('');    // login_response
        $amqpConfig->setLocale('en_EN');    // locale
        $amqpConfig->setConnectionTimeout($this->_parameters['connection_timeout']);
        $amqpConfig->setReadTimeout($this->_parameters['read_write_timeout']);
        $amqpConfig->setKeepalive($this->_parameters['keepalive']);
        $amqpConfig->setHeartbeat($this->_parameters['heartbeat']);
        $amqpConfig->setChannelRPCTimeout($this->_parameters['channel_rpc_timeout']);
        $amqpConfig->setIsLazy($this->_parameters['is_lazy']);
        if (
            $this->_parameters['ssl_options'] !== null
            && is_array($this->_parameters['ssl_options'])
        ) {
            $amqpConfig->setIsSecure(true);
            $amqpConfig->setSslCaCert($this->_parameters['ssl_options']['cafile']);
            $amqpConfig->setSslCaPath($this->_parameters['ssl_options']['capath']);
            $amqpConfig->setSslCert($this->_parameters['ssl_options']['local_cert']);
            $amqpConfig->setSslKey($this->_parameters['ssl_options']['local_pk']);
            $amqpConfig->setSslVerify($this->_parameters['ssl_options']['verify_peer']);
            $amqpConfig->setSslVerifyName($this->_parameters['ssl_options']['verify_peer_name']);
            $amqpConfig->setSslPassPhrase($this->_parameters['ssl_options']['passphrase']);
            $amqpConfig->setSslCiphers($this->_parameters['ssl_options']['ciphers']);
            $amqpConfig->setSslSecurityLevel($this->_parameters['ssl_options']['security_level']);
            $amqpConfig->setSslCryptoMethod($this->_parameters['ssl_options']['crypto_method']);
        }
        return AMQPConnectionFactory::create($amqpConfig);
    }

    /**
     * Parse connection defined by url, e.g. 'amqp://guest:password@localhost:5672/vhost?lazy=1&connection_timeout=6'
     * @param $parameters
     * @return array
     */
    private function parseUrl($parameters)
    {
        if (!$parameters['url']) {
            return $parameters;
        }
        $url = parse_url($parameters['url']);
        if ($url === false || !isset($url['scheme']) || $url['scheme'] !== 'amqp') {
            throw new \InvalidArgumentException('Malformed parameter "url".');
        }
        if (isset($url['host'])) {
            $parameters['host'] = urldecode($url['host']);
        }
        if (isset($url['port'])) {
            $parameters['port'] = (int)$url['port'];
        }
        if (isset($url['user'])) {
            $parameters['user'] = urldecode($url['user']);
        }
        if (isset($url['pass'])) {
            $parameters['password'] = urldecode($url['pass']);
        }
        if (isset($url['path'])) {
            $parameters['vhost'] = urldecode(ltrim($url['path'], '/'));
        }
        if (isset($url['query'])) {
            $query = [];
            parse_str($url['query'], $query);
            $parameters = array_merge($parameters, $query);
        }
        unset($parameters['url']);

        return $parameters;
    }
}
