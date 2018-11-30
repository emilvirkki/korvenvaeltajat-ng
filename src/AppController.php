<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Contentful\Delivery\Query;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        $events = $this->getEntries(
            $this->query('event')
                ->setLimit(7)
                ->orderBy('fields.datetimeStart')
                ->where('fields.datetimeStart', new \DateTime('today 00:00'), 'gte')
        );

        $articles = $this->getEntries(
            $this->query('article')->setLimit(3)
        );

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
        $article = $this->getEntries(
            $this->query('article')
        );

        return $this->renderTemplate('articles', array(
          'articles' => $article,
        ));
    }

    /**
     * @Route("/ajankohtaista/{slug}", name="article")
     */
    public function article($slug)
    {
        $article = $this->getEntry(
            $this->query('article')->where('fields.slug', $slug)
        );

        return $this->renderTemplate('article', array(
          'article' => $article,
        ));
    }

    /**
     * @Route("/tapahtumat", name="events")
     */
    public function events()
    {
        $events = $this->getEntries(
            $this->query('event')
                ->orderBy('fields.datetimeStart')
                ->where('fields.datetimeStart', new \DateTime('today 00:00'), 'gte')
        );

        return $this->renderTemplate('events', array(
          'events' => $events,
        ));
    }

    /**
     * @Route("/tapahtumat/{slug}", name="event")
     */
    public function event($slug)
    {
        $event = $this->getEntry(
            $this->query('event')->where('fields.slug', $slug)
        );

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

    private function getSnippets()
    {
        $entries_short = $this->getEntries($this->query('snippetShort'));
        $entries_long = $this->getEntries($this->query('snippetLong'));
        $entries = array_merge(
            iterator_to_array($entries_short),
            iterator_to_array($entries_long)
        );

        $snippets = array();
        foreach ($entries as $entry) {
            $id = $entry['id'];
            $snippets[$id] = $entry['content'];
        }
        return $snippets;
    }

    private function query($content_type)
    {
        $query = new Query();
        return $query->setContentType($content_type);
    }

    private function getEntries($query)
    {
        $client = $this->get('contentful.delivery');
        return $client->getEntries($query);
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
}
