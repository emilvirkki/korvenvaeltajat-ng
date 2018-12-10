<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{
    private $cache;

    function __construct(ContentService $content, CacheInterface $cache)
    {
        $this->content = $content;
        $this->cache = $cache;
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        $events = $this->cached('events.latest', function() {
            return $this->content->getAll(array(
                'content_type' => 'event',
                'limit' => 7,
                'order_by' => 'fields.datetimeStart',
                'where' => array(
                    array('fields.datetimeStart', new \DateTime('today 00:00'), 'gte')
                )
            ));
        });

        $articles = $this->cached('articles.latest', function() {
            return $this->content->getAll(array(
                'content_type' => 'article',
                'order_by' => '-sys.createdAt',
                'limit' => 3,
            ));
        });

        return $this->renderTemplate('home', array(
          'articles' => $articles,
          'events' => $events,
        ));
    }

    /**
     * @Route("/ajankohtaista", name="articles")
     */
    public function articles()
    {
        //TODO Add paging
        $article = $this->cached('articles', function() {
            return $this->content->getAll(array(
                'content_type' => 'article',
                'order_by' => '-sys.createdAt',
            ));
        });

        return $this->renderTemplate('articles', array(
          'articles' => $article,
        ));
    }

    /**
     * @Route("/ajankohtaista/{slug}", name="article")
     */
    public function article($slug)
    {
        $article = $this->cached('articles.'.md5($slug), function() use ($slug) {
            return $this->content->getOne(array(
                'content_type' => 'article',
                'fields.slug' => $slug,
            ));
        });

        if ($article === null) {
            throw $this->createNotFoundException('Artikkelia ei löydy');
        }

        return $this->renderTemplate('article', array(
          'article' => $article,
        ));
    }

    /**
     * @Route("/tapahtumat", name="events")
     */
    public function events()
    {
        $events = $this->cached('events', function() {
            return $this->content->getAll(array(
                'content_type' => 'event',
                'order_by' => 'fields.datetimeStart',
                'where' => array(
                    array('fields.datetimeStart', new \DateTime('today 00:00'), 'gte'),
                ),
            ));
        });

        return $this->renderTemplate('events', array(
          'events' => $events,
        ));
    }

    /**
     * @Route("/tapahtumat/{slug}", name="event")
     */
    public function event($slug)
    {
        $event = $this->cached('events.'.md5($slug), function() use ($slug) {
            return $this->content->getOne(array(
                'content_type' => 'event',
                'fields.slug' => $slug,
            ));
        });

        if ($event === null) {
            throw $this->createNotFoundException('Tapahtumaa ei löydy');
        }

        return $this->renderTemplate('event', array(
          'event' => $event,
        ));
    }

    /**
     * @Route("/mika-partio", name="about_scouting")
     */
    public function aboutScouting()
    {
        return $this->renderTemplate('about_scouting');
    }

    /**
     * @Route("/lippukunta", name="troop")
     */
    public function troop()
    {
        return $this->renderTemplate('troop');
    }

    /**
     * @Route("/yhteystiedot", name="contact")
     */
    public function contact()
    {
        return $this->renderTemplate('contact');
    }

    /**
     * @Route("/liity", name="join")
     */
    public function join()
    {
        return $this->renderTemplate('join');
    }

    /**
     * @Route("/clear-cache", name="clear_cache")
     */
    public function resetCache()
    {
        $this->cache->clear();
        return new Response('Cache cleared');
    }

    public function error(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null
    ) {
        if ($exception->getStatusCode() === 404) {
            return $this->renderTemplate('error404');
        }
        // Error might be from Contentful -> we can only render static stuff
        return $this->render('error.html.twig');
    }

    // Helper functions

    private function getSnippets()
    {
        return $this->cached('snippets', function() {
            $entries_short = $this->content->getAll(array('content_type' => 'snippetShort'));
            $entries_long = $this->content->getAll(array('content_type' => 'snippetLong'));
            $entries = array_merge($entries_short, $entries_long);

            $snippets = array();
            foreach ($entries as $entry) {
                $id = $entry['id'];
                $snippets[$id] = $entry['content'];
            }
            return $snippets;
        });
    }

    private function cached($key, $func)
    {
        if(!$this->cache->has($key)) {
            $this->cache->set($key, $func());
        }
        return $this->cache->get($key);
    }

    private function renderTemplate($name, $vars = array())
    {
        $vars = array_merge($vars, array(
            'template' => $name,
            'snippets' => $this->getSnippets(),
        ));
        return $this->render("$name.html.twig", $vars);
    }
}
