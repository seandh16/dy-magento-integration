<?php

namespace DynamicYield\Integration\Console;

use DynamicYield\Integration\Model\HeartBeat as HeartBeatModel;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HeartBeat extends Command
{
    /**
     * @var State
     */
    protected $_state;

    /**
     * @var HeartBeatModel
     */
    protected $_heartBeat;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * HeartBeat constructor
     *
     * @param State $state
     * @param null $name
     */
    public function __construct(
        State $state,
        HeartBeatModel $heartBeat,
        LoggerInterface $logger,
        $name = null
    )
    {
        parent::__construct($name);

        $this->_state = $state;
        $this->_heartBeat = $heartBeat;
        $this->_logger = $logger;
    }

    /**
     * Set unlimited on console
     */
    protected function setUnlimited()
    {
        set_time_limit(0);
    }

    /**
     * Configure console
     */
    public function configure()
    {
        $this->setName('dy:heartbeat')
            ->setDescription('Do heartbeat for cronjobs');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setUnlimited();

        try {
            $this->_state->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {}

        try {
            $this->_heartBeat->newBeat();
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }
    }
}