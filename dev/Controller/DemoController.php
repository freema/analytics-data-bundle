<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Dev\Controller;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClientInterface;
use Freema\GA4AnalyticsDataBundle\Client\AnalyticsRegistryInterface;
use Freema\GA4AnalyticsDataBundle\Domain\Period;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemoController extends AbstractController
{
    #[Route('/', name: 'demo_index')]
    public function index(AnalyticsRegistryInterface $analyticsRegistry): Response
    {
        // Check if we have the default client
        $clients = $analyticsRegistry->getClients();
        $hasDefaultClient = isset($clients['default']);
        
        return $this->render('@GA4AnalyticsData/demo/index.html.twig', [
            'clients' => array_keys($clients),
            'hasDefaultClient' => $hasDefaultClient,
        ]);
    }
    
    #[Route('/dashboard', name: 'demo_dashboard')]
    public function dashboard(AnalyticsClientInterface $analyticsClient): Response
    {
        try {
            // Get data for last 30 days
            $period = Period::days(30);
            
            // Get most viewed pages
            $topPages = $analyticsClient->getMostViewedPages($period, 10);
            
            // Get visitors and pageviews by date
            $visitorsData = $analyticsClient->getVisitorsAndPageViews($period);
            
            // Get total visitors and pageviews
            $totals = $analyticsClient->getTotalVisitorsAndPageViews($period);
            
            return $this->render('@GA4AnalyticsData/demo/dashboard.html.twig', [
                'topPages' => $topPages,
                'visitorsData' => $visitorsData,
                'totals' => $totals,
            ]);
        } catch (\Exception $e) {
            return $this->render('@GA4AnalyticsData/demo/error.html.twig', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}