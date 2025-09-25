<?php

namespace Drupal\islandora_workbench_integration\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Event Subscriber for automatic translation creation.
 */
class RequestEventSubscriber implements EventSubscriberInterface {

  /**
   * Code that should be triggered on event specified.
   */
  public function onRequest(RequestEvent $event) {

    // Only preload on json/api requests.
    if ($event->getRequest()->getRequestFormat() == 'json' && $event->getRequest()->getMethod() == 'PATCH') {
      list(, $language, $bundle, $path_part_3, $path_part_4) = explode('/', $event->getRequest()->getPathInfo());

      // Create translation only if POST request contains language param.
      if (!empty($language)) {

        // Need to load node and taxonomy term differently.
        if ($bundle == "node") {
          $nid = $path_part_3;
          $node = Node::load($path_part_3);
          if ($node && !$node->hasTranslation($language)) {
            \Drupal::logger('islandora_workbench_integration')->debug(
              "Node with ID @id has no '@lang' translation: create it!", [
                '@id' => $nid,
                '@lang' => $language,
              ]
            );
            $node->addTranslation($language, ['title' => $node->label()])->save();
          }
        }
        elseif ($bundle == "taxonomy") {
          $tid = $path_part_4;
          $term = Term::load($tid);
          if ($term && !$term->hasTranslation($language)) {
            \Drupal::logger('islandora_workbench_integration')->debug(
              "Term with ID @id has no '@lang' translation: create it!", [
                '@id' => $tid,
                '@lang' => $language,
              ]
            );
            $term->addTranslation($language, ['name' => $term->label()])->save();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We need to run before routing.
    $events[KernelEvents::REQUEST][] = ['onRequest', 1000];
    return $events;
  }

}