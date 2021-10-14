<?php

namespace Drupal\uikit;

/**
 * Provides helper functions for the UIkit base theme.
 */
class UIkit {

  /**
   * The UIkit library project page.
   *
   * @var string
   */
  const UIKIT_LIBRARY = 'https://getuikit.com/';

  /**
   * The UIkit library version supported in the UIkit base theme.
   *
   * @var string
   */
  const UIKIT_LIBRARY_VERSION = '3.5.9';

  /**
   * The Drupal project page for the UIkit base theme.
   *
   * @var string
   */
  const UIKIT_PROJECT = 'https://www.drupal.org/project/uikit';

  /**
   * The Drupal project branch for the UIkit base theme.
   *
   * @var string
   */
  const UIKIT_PROJECT_BRANCH = '8.x-3.x';

  /**
   * The Drupal project API site for the UIkit base theme.
   *
   * @var string
   */
  const UIKIT_PROJECT_API = 'http://uikit-drupal.com';

  /**
   * Loads a project's include file.
   *
   * This function essentially does the same as Drupal core's
   * module_load_include() function, except targeting theme include files. It also
   * allows you to place the include files in a sub-directory of the theme for
   * better organization.
   *
   * Examples:
   * @code
   *   // Load node.admin.inc from the node module.
   *   UIkitComponents::loadIncludeFile('inc', 'node', 'module', 'node.admin');
   *
   *   // Load includes/alter.inc from the uikit theme.
   *   UIkitComponents::loadIncludeFile('inc', 'uikit', 'theme', 'preprocess', 'includes');
   * @endcode
   *
   * Do not use this function in a global context since it requires Drupal to be
   * fully bootstrapped, use require_once DRUPAL_ROOT . '/path/file' instead.
   *
   * @param string $type
   *   The include file's type (file extension).
   * @param string $project
   *   The project to which the include file belongs.
   * @param string $project_type
   *   The project type to which the include file belongs, either "theme" or
   *   "module". Defaults to "module".
   * @param string $name
   *   (optional) The base file name (without the $type extension). If omitted,
   *   $theme is used; i.e., resulting in "$theme.$type" by default.
   * @param string $sub_directory
   *   (optional) The sub-directory to which the include file resides.
   *
   * @return string
   *   The name of the included file, if successful; FALSE otherwise.
   */
  public static function loadIncludeFile($type, $project, $project_type = 'module', $name = NULL, $sub_directory = '') {
    static $files = [];

    if (isset($sub_directory)) {
      $sub_directory = '/' . $sub_directory;
    }

    if (!isset($name)) {
      $name = $project;
    }

    $key = $type . ':' . $project . ':' . $name . ':' . $sub_directory;

    if (isset($files[$key])) {
      return $files[$key];
    }

    if (function_exists('drupal_get_path')) {
      $file = DRUPAL_ROOT . '/' . drupal_get_path($project_type, $project) . "$sub_directory/$name.$type";
      if (is_file($file)) {
        require_once $file;
        $files[$key] = $file;
        return $file;
      } else {
        $files[$key] = FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Retrieves the active theme.
   *
   * @return
   *   The active theme's machine name.
   */
  public static function getActiveTheme()
  {
    return \Drupal::theme()->getActiveTheme()->getName();
  }

  /**
   * Retrieves a theme setting.
   *
   * @param null $setting
   *   The machine-name of the theme setting to retrieve.
   * @param $theme
   *   The theme to retrieve the setting for. Defaults to the active theme.
   *
   * @return mixed
   *   The theme setting's value.
   */
  public static function getThemeSetting($setting, $theme = NULL) {
    if (empty($theme)) {
      $theme = self::getActiveTheme();
    }

    if (!empty($setting)) {
      return theme_get_setting($setting, $theme);
    } else {
      throw new \LogicException('Missing argument $setting');
    }
  }

  /**
   * Retrieves the grid classes used in page.html.twig.
   *
   * @param bool $sidebar_first
   *   True if the sidebar_first region has content, false otherwise.
   * @param bool $sidebar_second
   *   True if the sidebar_second region has content, false otherwise.
   *
   * @return array
   *   An array of grid classes to use in page.html.twig.
   */
  public static function getGridClasses($sidebar_first = FALSE, $sidebar_second = FALSE) {
    $standard_layout = self::getThemeSetting('standard_sidebar_positions');
    $tablet_layout = self::getThemeSetting('tablet_sidebar_positions');
    $mobile_layout = self::getThemeSetting('mobile_sidebar_positions');

    $grid_classes = [
      'content' => [],
      'sidebar' => [],
    ];

    if ($sidebar_first) {
      $grid_classes['sidebar']['first'] = [];
    }
    if ($sidebar_second) {
      $grid_classes['sidebar']['second'] = [];
    }

    if ($sidebar_first && $sidebar_second) {
      $grid_classes['content'][] = 'uk-width-1-2@l';
      $grid_classes['sidebar']['first'][] = 'uk-width-1-4@l';
      $grid_classes['sidebar']['second'][] = 'uk-width-1-4@l';

      switch ($standard_layout) {
        case 'holy-grail':
          $grid_classes['content'][] = 'uk-push-large-1-4';
          $grid_classes['sidebar']['first'][] = 'uk-pull-1-2@l';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@l';
          break;

        case 'sidebars-left':
          $grid_classes['content'][] = 'uk-push-1-2@l';
          $grid_classes['sidebar']['first'][] = 'uk-pull-1-2@l';
          $grid_classes['sidebar']['second'][] = 'uk-pull-1-2@l';
          break;

        case 'sidebars-right':
          $grid_classes['content'][] = 'uk-push-pull-@l';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@l';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@l';
          break;
      }

      switch ($tablet_layout) {
        case 'holy-grail':
          $grid_classes['content'][] = 'uk-width-1-2@m';
          $grid_classes['content'][] = 'uk-push-1-4@m';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-pull-1-2@m';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@m';
          break;

        case 'sidebars-left':
          $grid_classes['content'][] = 'uk-width-1-2@m';
          $grid_classes['content'][] = 'uk-push-1-2@m';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-pull-1-2@m';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-pull-1-2@m';
          break;

        case 'sidebars-right':
          $grid_classes['content'][] = 'uk-width-1-2@m';
          $grid_classes['content'][] = 'uk-push-pull-@m';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@m';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@m';
          break;

        case 'sidebar-left-stacked':
          $grid_classes['content'][] = 'uk-width-3-4@m';
          $grid_classes['content'][] = 'uk-push-1-4@m';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-pull-3-4@m';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-1@m';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@m';
          break;

        case 'sidebar-right-stacked':
          $grid_classes['content'][] = 'uk-width-3-4@m';
          $grid_classes['content'][] = 'uk-push-pull-@m';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@m';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-1@m';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@m';
          break;
      }

      switch ($mobile_layout) {
        case 'sidebars-stacked':
          $grid_classes['content'][] = 'uk-width-1-1@s';
          $grid_classes['content'][] = 'uk-width-1-1';
          $grid_classes['content'][] = 'uk-push-pull-@s';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-1@s';
          $grid_classes['sidebar']['first'][] = 'uk-width-1-1';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@s';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-1@s';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-1';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@s';
          break;

        case 'sidebars-vertical':
          $grid_classes['content'][] = 'uk-width-1-1@s';
          $grid_classes['content'][] = 'uk-width-1-1';
          $grid_classes['content'][] = 'uk-push-pull-@s';

          $grid_classes['sidebar']['first'][] = 'uk-width-1-2@s';
          $grid_classes['sidebar']['first'][] = 'uk-width-1-2';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@s';

          $grid_classes['sidebar']['second'][] = 'uk-width-1-2@s';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-2';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@s';
          break;
      }
    } elseif ($sidebar_first) {
      $grid_classes['content'][] = 'uk-width-3-4@l';
      $grid_classes['sidebar']['first'][] = 'uk-width-1-4@l';
      $grid_classes['content'][] = 'uk-width-3-4@m';
      $grid_classes['sidebar']['first'][] = 'uk-width-1-4@m';

      switch ($standard_layout) {
        case 'holy-grail':
          $grid_classes['content'][] = 'uk-push-1-4@l';
          $grid_classes['sidebar']['first'][] = 'uk-pull-3-4@l';
          break;

        case 'sidebars-left':
          $grid_classes['content'][] = 'uk-push-1-4@l';
          $grid_classes['sidebar']['first'][] = 'uk-pull-3-4@l';
          break;

        case 'sidebars-right':
          $grid_classes['content'][] = 'uk-push-pull-@l';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@l';
          break;
      }

      switch ($tablet_layout) {
        case 'holy-grail':
          $grid_classes['content'][] = 'uk-push-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-pull-3-4@m';
          break;

        case 'sidebars-left':
          $grid_classes['content'][] = 'uk-push-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-pull-3-4@m';
          break;

        case 'sidebars-right':
          $grid_classes['content'][] = 'uk-push-pull-@m';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@m';
          break;

        case 'sidebar-left-stacked':
          $grid_classes['content'][] = 'uk-push-1-4@m';
          $grid_classes['sidebar']['first'][] = 'uk-pull-3-4@m';
          break;

        case 'sidebar-right-stacked':
          $grid_classes['content'][] = 'uk-push-pull-@m';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@m';
          break;
      }
    } elseif ($sidebar_second) {
      $grid_classes['content'][] = 'uk-width-3-4@l';
      $grid_classes['sidebar']['second'][] = 'uk-width-1-4@l';

      switch ($standard_layout) {
        case 'holy-grail':
          $grid_classes['content'][] = 'uk-push-pull-@l';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@l';
          break;

        case 'sidebars-left':
          $grid_classes['content'][] = 'uk-push-1-4@l';
          $grid_classes['sidebar']['second'][] = 'uk-pull-3-4@l';
          break;

        case 'sidebars-right':
          $grid_classes['content'][] = 'uk-push-pull-@l';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@l';
          break;
      }

      switch ($tablet_layout) {
        case 'holy-grail':
          $grid_classes['content'][] = 'uk-width-3-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-4@m';
          $grid_classes['content'][] = 'uk-push-pull-@m';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@m';
          break;

        case 'sidebars-left':
          $grid_classes['content'][] = 'uk-width-3-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-4@m';
          $grid_classes['content'][] = 'uk-push-1-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-pull-3-4@m';
          break;

        case 'sidebars-right':
          $grid_classes['content'][] = 'uk-width-3-4@m';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-4@m';
          $grid_classes['content'][] = 'uk-push-pull-@m';
          $grid_classes['sidebar']['first'][] = 'uk-push-pull-@m';
          break;

        case 'sidebar-left-stacked':
          $grid_classes['content'][] = 'uk-width-1-1@m';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-1@m';
          $grid_classes['content'][] = 'uk-push-pull-@m';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@m';
          break;

        case 'sidebar-right-stacked':
          $grid_classes['content'][] = 'uk-width-1-1@m';
          $grid_classes['sidebar']['second'][] = 'uk-width-1-1@m';
          $grid_classes['content'][] = 'uk-push-pull-@m';
          $grid_classes['sidebar']['second'][] = 'uk-push-pull-@m';
          break;
      }
    } else {
      $grid_classes['content'][] = 'uk-width-1-1';
    }

    // Allow sub-themes to make adjustments.
    \Drupal::theme()->alter('uikit_grid_classes', $grid_classes);

    return $grid_classes;
  }

  /**
   * Retrieves the current page title.
   *
   * @return string
   *   The current page title.
   */
  public static function getPageTitle() {
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    return \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
  }
}
