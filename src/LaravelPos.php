<?php

namespace Mews\LaravelPos;

use Illuminate\Config\Repository;
use Mews\Pos\Entity\Account\AbstractPosAccount;
use Mews\Pos\Entity\Card\AbstractCreditCard;
use Mews\Pos\Exceptions\BankClassNullException;
use Mews\Pos\Exceptions\BankNotFoundException;
use Mews\Pos\Factory\PosFactory;
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
     * @param AbstractPosAccount $account
     * @return $this
     * @throws BankClassNullException
     * @throws BankNotFoundException
     */
    public function account(AbstractPosAccount $account)
    {
        $this->account = $account;

        $this->bank = PosFactory::createPosGateway($account);

        return $this;
    }

    /**
     * Prepare Order
     *
     * @param array                   $order
     * @param string                  $txType
     * @param AbstractCreditCard|null $card
     *
     * @return $this
     */
    public function prepare(array $order, string $txType, AbstractCreditCard $card = null)
    {
        $this->bank->prepare($order, $txType, $card);

        return $this;
    }

    /**
     * Payment
     *
     * @param AbstractCreditCard|null $card
     *
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Mews\Pos\Exceptions\UnsupportedPaymentModelException
     */
    public function payment(AbstractCreditCard $card = null)
    {
        $this->bank->payment($card);

        $this->response = $this->bank->getResponse();

        return $this;
    }

    /**
     * Get gateway URL
     *
     * @return string|null
     */
    public function getGatewayUrl()
    {
        return $this->bank->gateway ?? 'null';
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
            $data = $this->bank->get3dFormData();
        } catch (\Exception $e) {}

        return $data;
    }

    /**
     * Is success
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->bank->isSuccess();
    }

    /**
     * Is error
     *
     * @return bool
     */
    public function isError()
    {
        return $this->bank->isError();
    }
}
