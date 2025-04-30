<?php
namespace Gibbon\Module\ChatBot;

use Gibbon\Module\ChatBot\Domain\ChatBotGateway;
use Gibbon\Module\ChatBot\Domain\TrainingGateway;
use Gibbon\Module\ChatBot\Services\DeepSeekService;
use Gibbon\Module\ChatBot\Services\AssessmentService;
use Gibbon\Module\ChatBot\Services\TrainingService;

class Module
{
    private $container;
    private $chatBotGateway;
    private $trainingGateway;
    private $deepSeekService;
    private $assessmentService;
    private $trainingService;

    public function __construct($container)
    {
        $this->container = $container;
        $this->chatBotGateway = new ChatBotGateway($container->get('db'));
        $this->trainingGateway = new TrainingGateway($container->get('db'));
        $this->deepSeekService = new DeepSeekService($container);
        $this->assessmentService = new AssessmentService($container);
        $this->trainingService = new TrainingService($container);
    }

    public function getChatBotGateway()
    {
        return $this->chatBotGateway;
    }

    public function getTrainingGateway()
    {
        return $this->trainingGateway;
    }

    public function getDeepSeekService()
    {
        return $this->deepSeekService;
    }

    public function getAssessmentService()
    {
        return $this->assessmentService;
    }

    public function getTrainingService()
    {
        return $this->trainingService;
    }
} 