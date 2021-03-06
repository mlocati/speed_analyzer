<?php

namespace A3020\SpeedAnalyzer\Provider;

use A3020\SpeedAnalyzer\Client;
use A3020\SpeedAnalyzer\Request\Tracker;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Routing\RouterInterface;
use Doctrine\ORM\EntityManager;

class SpeedAnalyzerServiceProvider implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /** @var Repository */
    protected $config;

    /** @var Client */
    private $client;

    /** @var RouterInterface */
    private $router;

    public function __construct(Repository $config, RouterInterface $router, Client $client)
    {
        $this->config = $config;
        $this->router = $router;
        $this->client = $client;
    }

    public function register()
    {
        $this->bindings();
        $this->registerRoutes();
        $this->client->start();

        $this->app['director']->addListener('on_user_delete', function($event) {
            // Delete Reports if a user is deleted. #gdpr
            $this->app->make(\A3020\SpeedAnalyzer\Listener\OnUserDelete\DeleteReports::class)
                ->handle($event);
        });

        $this->app['director']->dispatch('on_speed_analyzer_started');
    }

    private function bindings()
    {
        $this->app->singleton(Tracker::class, Tracker::class);

        // Make sure the report event repository is injected
        $this->app->when(\A3020\SpeedAnalyzer\Event\EventRepository::class)
            ->needs(\Doctrine\ORM\EntityRepository::class)
            ->give(function(){
                return $this->app->make(EntityManager::class)
                    ->getRepository(\A3020\SpeedAnalyzer\Entity\ReportEvent::class);
            });
    }

    private function registerRoutes()
    {
        $this->router->registerMultiple([
            '/ccm/system/speed_analyzer/reports' => [
                '\A3020\SpeedAnalyzer\Ajax\Reports::getPage',
            ],
            '/ccm/system/speed_analyzer/query/{eventId}' => [
                '\A3020\SpeedAnalyzer\Ajax\QueryDetails::view',
            ],
            'ccm/system/speed_optimizer/diagnosis/environment' => [
                '\A3020\SpeedAnalyzer\Ajax\Diagnosis\Environment::view',
            ],
            'ccm/system/speed_optimizer/diagnosis/location' => [
                '\A3020\SpeedAnalyzer\Ajax\Diagnosis\Location::view',
            ],
            'ccm/system/speed_optimizer/diagnosis/packages' => [
                '\A3020\SpeedAnalyzer\Ajax\Diagnosis\Packages::view',
            ],
        ]);
    }
}
