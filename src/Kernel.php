<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

require __DIR__.'/../vendor/autoload.php';

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Contentful\ContentfulBundle\ContentfulBundle(),
            new \Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
        );
    }

    protected function configureContainer(ContainerConfigurator $c)
    {
        $c->extension('framework', array(
            'secret' => 'S0ME_SECRET'
        ));

        $c->extension('contentful', array(
            'delivery' => array(
                'main' => array(
                    'space' => $_ENV['CONTENTFUL_SPACE'],
                    'token' => $_ENV['CONTENTFUL_TOKEN'],
                    'environment' => $_ENV['CONTENTFUL_ENV'],
                )
            )
        ));

        // Use custom controller action to show errors, since we still need
        // to load e.g. snippets on 404 pages (prod only, in dev we want to see error info)
        // FIXME: the custom error pages are not triggered currently
        if (!$this->debug) {
            $c->extension('framework', array(
                'error_controller' => 'App\AppController::error',
            ));
        }

        if ($this->getEnvironment() === 'prod') {
            $c->services()->set('cache', \Symfony\Component\Cache\Adapter\FilesystemAdapter::class);
        } else {
            $c->services()->set('cache', \Symfony\Component\Cache\Adapter\ArrayAdapter::class);
        }

        $c->services()->set('content', ContentfulContentService::class);

        $c->services()->load('App\\', __DIR__.'/*')
            ->autowire()
            ->autoconfigure();

        $c->services()->set('twig.extension.text', \Twig\Extensions\TextExtension::class)
            ->tag('twig.extension');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__, '/', 'annotation');
    }

    public function getCacheDir() {
        return __DIR__.'/../cache';
    }

    public function getLogDir() {
        return __DIR__.'/../logs';
    }
}
