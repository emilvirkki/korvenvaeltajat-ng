<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\DependencyInjection\Reference;

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

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->loadFromExtension('framework', array(
            'secret' => 'S0ME_SECRET',
        ));

        $c->loadFromExtension('contentful', array(
            'delivery' => array(
                'space' => getenv('CONTENTFUL_SPACE'),
                'token' => getenv('CONTENTFUL_TOKEN'),
                'environment' => getenv('CONTENTFUL_ENV'),
                'cache' => true,
            )
        ));

        // Use custom controller action to show errors, since we still need
        // to load e.g. snippets on 404 pages (prod only, in dev we want to see error info)
        if (!$this->isDebug()) {
            $c->loadFromExtension('twig', array(
                'exception_controller' => 'App\AppController::error',
            ));
        }

        if ($this->getEnvironment() === 'prod') {
            $c->register('cache', \Symfony\Component\Cache\Simple\FilesystemCache::class);
        } else {
            $c->register('cache', \Symfony\Component\Cache\Simple\ArrayCache::class);
        }

        $c->register('content', ContentfulContentService::class)
            ->setPublic(true)
            ->addArgument(new Reference('contentful.delivery'));

        $c->register(AppController::class)
            ->setPublic(true)
            ->addArgument(new Reference('content'))
            ->addArgument(new Reference('cache'));

        $c->register('twig.extension.text', \Twig\Extensions\TextExtension::class)
            ->setPublic(false)
            ->addTag('twig.extension');
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
