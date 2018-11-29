<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
            )
        ));

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
