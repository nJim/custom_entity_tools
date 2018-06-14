<?php

namespace Drupal\custom_entity_tools\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityBaseController.
 *
 * @todo: Refactor: This controller is unorganized and the methods are too long.
 * @todo: function format_date and method l() are deprecated.
 *
 * Returns responses for revision routs on EntityBase entities.
 */
class EntityBaseController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRoute;

  /**
   * The definition of the current entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The machine-name of the current entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Constructs a new EntityBaseRevisionRevertForm.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity storage.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRoute = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeId = $this->currentRoute->getRouteObject()->getOption('_entity_type_id');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Routing\RouteMatchInterface $routeMatch */
    $routeMatch = $container->get('current_route_match');
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityType */
    $entityType = $container->get('entity_type.manager');
    return new static(
      $routeMatch,
      $entityType
    );
  }

  /**
   * Displays a EntityBase revision.
   *
   * @param int $revision
   *   The EntityBase revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionShow($revision) {
    $entity = $this->entityTypeManager()->getStorage($this->entityTypeId)->loadRevision($revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder($this->entityTypeId);
    return $view_builder->view($entity);
  }

  /**
   * Page title callback for a EntityBase revision.
   *
   * @param int $revision
   *   The EntityBase revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionPageTitle($revision) {
    /* @var \Drupal\custom_entity_tools\Entity\EntityBase $entity */
    $entity = $this->entityTypeManager()->getStorage($this->entityTypeId)->loadRevision($revision);
    return $this->t('Revision of %title from %date', ['%title' => $entity->label(), '%date' => format_date($entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of an EntityBase entity.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionOverview() {
    $entityTypeId = $this->entityTypeId;
    $entity = $this->currentRoute->getParameter($entityTypeId);
    $account = $this->currentUser();
    $langcode = $entity->language()->getId();
    $langname = $entity->language()->getName();
    $languages = $entity->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);
    /* @var \Drupal\custom_entity_tools\EntityBaseStorage $entityStorage */
    $entityStorage = $this->entityTypeManager()->getStorage($this->entityTypeId);

    $build['#title'] = $hasTranslations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $entity->label()]) : $this->t('Revisions for %title', ['%title' => $entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revertPermission = (($account->hasPermission("revert $entityTypeId revisions") || $account->hasPermission("administer $entityTypeId entities")));
    $deletePermission = (($account->hasPermission("delete $entityTypeId revisions") || $account->hasPermission("administer $entityTypeId entities")));

    $rows = [];

    $vids = $entityStorage->revisionIds($entity);

    $latestRevision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\custom_entity_tools\Entity\EntityBaseInterface $revision */
      $revision = $entityStorage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $entity->getRevisionId()) {
          $link = $this->l($date, new Url("entity.$entityTypeId.revision", [$entityTypeId => $entity->id(), 'revision' => $vid]));
        }
        else {
          $link = $entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latestRevision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latestRevision = FALSE;
        }
        else {
          $links = [];
          if ($revertPermission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute("entity.$entityTypeId.revision_revert_confirm", [
                $entityTypeId => $entity->id(),
                'revision' => $vid,
              ]),
            ];
          }

          if ($deletePermission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute("entity.$entityTypeId.revision_delete_confirm", [
                $entityTypeId => $entity->id(),
                'revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
