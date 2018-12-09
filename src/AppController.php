<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Contentful\Delivery\Query;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{
    private $cache;

    function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        $events = $this->cached('events.latest', function() {
            return $this->getEntries(
                $this->query('event')
                    ->setLimit(7)
                    ->orderBy('fields.datetimeStart')
                    ->where('fields.datetimeStart', new \DateTime('today 00:00'), 'gte')
            );
        });

        $articles = $this->cached('articles.latest', function() {
            return $this->getEntries(
                $this->query('article')
                    ->orderBy('-sys.createdAt')
                    ->setLimit(3)
            );
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
            return $this->getEntries(
                    $this->query('article')->orderBy('-sys.createdAt')
            );
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
            return $this->getEntry(
                $this->query('article')->where('fields.slug', $slug)
            );
        });

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
            return $this->getEntries(
                $this->query('event')
                    ->orderBy('fields.datetimeStart')
                    ->where('fields.datetimeStart', new \DateTime('today 00:00'), 'gte')
            );
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
            return $this->getEntry(
                $this->query('event')->where('fields.slug', $slug)
            );
        });

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
            $entries_short = $this->getEntries($this->query('snippetShort'));
            $entries_long = $this->getEntries($this->query('snippetLong'));
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

    // TODO refactor Contentful stuff to service

    private function query($content_type)
    {
        $query = new Query();
        return $query->setContentType($content_type);
    }

    private function getEntries($query)
    {
        $client = $this->get('contentful.delivery');
        return $this->entriesToArray(
            $client->getEntries($query)
        );
    }

    private function getEntry($query)
    {
        $query->setLimit(1);
        $entries = $this->getEntries($query);

        if (count($entries) === 0) {
            throw $this->createNotFoundException('Artikkelia ei löydy');
        }

        return $entries[0];
    }

    private function renderTemplate($name, $vars = array())
    {
        $vars = array_merge($vars, array(
            'template' => $name,
            'snippets' => $this->getSnippets(),
        ));
        return $this->render("$name.html.twig", $vars);
    }

    // TODO refactor these to service

    private function entriesToArray($raw_entries)
    {
        $entries = array();
        foreach ($raw_entries as $raw_entry) {
            $entries[] = $this->entryToArray($raw_entry);
        }
        return $entries;
    }

    private function entryToArray($entry)
    {
        $fields = $entry->getContentType()->getFields();
        $arr = array();
        foreach ($fields as $id => $field) {
            if ($field->getType() === 'Array') {
                $arr[$id] = $this->arrayFieldToArray($entry[$id], $field);
            } else {
                // Some field types e.g. links will throw an error here - that's fine for now
                $arr[$id] = strval($entry[$id]);
            }
        }
        $arr['created'] = $entry->getSystemProperties()->getCreatedAt();
        return $arr;
    }

    private function arrayFieldToArray($field_values, $field) {
        $arr = array();
        foreach ($field_values as $key => $value) {
            if ($field->getItemsLinkType() === 'Asset') {
                $arr[$key] = array(
                    'title' => $value->getTitle(),
                    'url' => $value->getFile()->getUrl(),
                    'content_type' => $value->getFile()->getContentType(),
                );
            } else {
                // Other link types will throw error here - that's fine for now
                $arr[$key] = strval($value);
            }
        }
        return $arr;
    }
}
