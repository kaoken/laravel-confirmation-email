<?php

namespace App\Library\Auth\Confirmation;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Auth\PasswordBrokerFactory as FactoryContract;
use App\Library\Auth\Confirmation\DatabaseTokenRepository;

/**
 * @mixin ConfirmationBroker
 */
class ConfirmationBrokerManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $brokers = [];

    /**
     * Create a new ConfirmationBroker manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the broker from the local cache.
     *
     * @param  string  $name
     * @return \App\Library\Auth\Confirmation\ConfirmationBroker
     */
    public function broker($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return isset($this->brokers[$name])
            ? $this->brokers[$name]
            : $this->brokers[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given broker.
     *
     * @param  string  $name
     * @return \App\Library\Auth\Confirmation\ConfirmationBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Confirmation [{$name}] is not defined.");
        }

        return new ConfirmationBroker(
            $config,
            $this->createTokenRepository($config),
            $config['model'],
            $config['path'],
            $this->app['mailer'],
            $config['email_confirmation'],
            $config['email_registration']
        );
    }


    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param  array  $config
     * @return ConfirmationDB
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $connection = isset($config['connection']) ? $config['connection'] : null;

        return new ConfirmationDB(
            $this->app['db']->connection($connection),
            $config['table'],
            $config['model'],
            $key,
            $config['expire']
        );
    }

    /**
     * Get the confirmation broker configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.confirmations.{$name}"];
    }

    /**
     * Get the default password broker name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.confirmations'];
    }

    /**
     * Set the default confirmation broker name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.confirmations'] = $name;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->broker(), $method], $parameters);
    }
}
