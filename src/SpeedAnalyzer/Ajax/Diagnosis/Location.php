<?php

namespace A3020\SpeedAnalyzer\Ajax\Diagnosis;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Response;
use Concrete\Core\Page\Page;
use Concrete\Core\View\View;
use Concrete\Core\Geolocator\GeolocationResult;
use Exception;

class Location extends \Concrete\Core\Controller\Controller implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    public function view()
    {
        $this->checkPermissions();

        $serverIp = $this->getServerIp();

        $view = new View('diagnosis/location');
        $view->setPackageHandle('speed_analyzer');
        $view->addScopeItems([
            'serverIp' => $serverIp,
            'clientIp' => $this->getClientIP(),
            'serverCountry' => $this->getServerCountry($serverIp),
            'clientCountry' => $this->getClientCountry(),
        ]);

        return Response::create($view->render());
    }

    public function checkPermissions()
    {
        $page = Page::getByPath('/dashboard/speed_analyzer');
        $cp = new \Permissions($page);
        if (!$page || $page->isError() || !$cp->canViewPage()) {
            throw new UserMessageException(t('Access Denied'));
        }
    }

    private function getServerCountry($serverIp)
    {
        if (!$serverIp) {
            return false;
        }

        try {
            /** @var GeolocationResult\ $geoLocationResult */
            $geoLocationResult = $this->app->make(GeoLocationResult::class, [
                'ip' => $serverIp,
            ]);

            return $geoLocationResult->getCountryNameLocalized();
        } catch (Exception $e) {
            return false;
        }
    }

    private function getClientCountry()
    {
        try {
            /** @var GeolocationResult\ $geoLocationResult */
            $geoLocationResult = $this->app->make(GeoLocationResult::class);

            return $geoLocationResult->getCountryNameLocalized();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return string|false
     */
    private function getServerIp()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        };

        return false;
    }

    private function getClientIP()
    {
        /* @var \Concrete\Core\Permission\IPService $ipService */
        $ipService = $this->app->make('ip');

        return (string) $ipService->getRequestIPAddress();
    }
}
