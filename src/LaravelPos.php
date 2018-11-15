<?php

namespace Mews\LaravelPos;

use Illuminate\Config\Repository;
use Mews\Pos\Pos;
use Mews\Pos\PosInterface;

/**
 * Class LaravelPos
 * @package Mews\LaravelPos
 */
class LaravelPos
{
    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $account;

    /**
     * @var Pos
     */
    protected $pos;

    /**
     * @var PosInterface
     */
    public $bank;

    /**
     * Response data
     *
     * @var object
     */
    public $response;

    /**
     * Constructor
     *
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config->get('laravel-pos');
    }

    /**
     * Instance
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Set custom configuration
     *
     * @param array $config
     * @return LaravelPos
     */
    public function config(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set account and create Pos Object
     *
     * @param array $account
     * @return $this
     * @throws \Mews\Pos\Exceptions\BankClassNullException
     * @throws \Mews\Pos\Exceptions\BankNotFoundException
     */
    public function account(array $account)
    {
        $this->account = $account;

        $this->pos = new Pos($this->account, $this->config);
        $this->bank = $this->pos->bank;

        return $this;
    }

    /**
     * Prepare Order
     *
     * @param array $order
     * @return $this
     */
    public function prepare(array $order)
    {
        $this->pos->prepare($order);

        return $this;
    }

    /**
     * Payment
     *
     * @param array $card
     * @return $this
     */
    public function payment(array $card)
    {
        $this->pos->payment($card);

        $this->response = $this->pos->bank->response;

        return $this;
    }

    /**
     * Get gateway URL
     *
     * @return string|null
     */
    public function getGatewayUrl()
    {
        return isset($this->pos->bank->gateway) ? $this->pos->bank->gateway : 'null';
    }

    /**
     * Get 3d Form Data
     *
     * @return array
     */
    public function get3dFormData()
    {
        $data = [];

        try {
            $data = $this->pos->bank->get3dFormData();
        } catch (Exception $e) {}

        return $data;
    }

    /**
     * Is success
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->pos->bank->isSuccess();
    }

    /**
     * Is error
     *
     * @return bool
     */
    public function isError()
    {
        return $this->pos->bank->isError();
    }
}
