<?php

namespace Drupal\Tests\language_country_negotiation\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\Url;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrlFallback;
use Drupal\language_country_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationLanguageCountryUrl;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\language_country_negotiation\Traits\RequestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests common redirect patterns.
 *
 * @group language_country_negotiation
 */
class NegotiationRedirectTest extends CountryTestBase {

  use RequestTrait;
  use NodeCreationTrait;
  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'node',
    'path_alias',
  ];

  /**
   * Redirects source to target for homepage.
   *
   * @var string[]
   */
  protected array $homeRedirects = [
    // International home page.
    '/' => '/',
    '/node/1' => '/',
    // Allowed language-country combinations.
    '/de-de' => '/de-de',
    '/nl-be' => '/nl-be',
    '/fr-be' => '/fr-be',
    '/de-be' => '/de-be',
    '/en-us' => '/en-us',
    '/en-kr' => '/en-kr',
    '/de-de/node/1' => '/de-de',
    '/nl-be/node/1' => '/nl-be',
    '/fr-be/node/1' => '/fr-be',
    '/de-be/node/1' => '/de-be',
    '/en-us/node/1' => '/en-us',
    '/en-kr/node/1' => '/en-kr',
    // Repairable language-country combinations.
    '/en-be' => '/nl-be',
    '/ko-kr' => '/en-kr',
    '/en-be/node/1' => '/nl-be',
    '/ko-kr/node/1' => '/en-kr',
    // Disabled language-country combinations.
    '/zh-cn' => '/',
    '/en-eg' => '/',
    '/fr-ma' => '/',
    '/zh-cn/node/1' => '/',
    '/en-eg/node/1' => '/',
    '/fr-ma/node/1' => '/',
    // Obscure language-country combination matching the pattern.
    '/xx-yy' => '/',
    '/xx-yy/node/1' => '/',
    // Language fallback for langcode prefix.
    '/de/node/1' => '/de',
    '/de' => '/de',
    '/fr/node/1' => '/fr',
    '/fr' => '/fr',
    '/nl/node/1' => '/nl',
    '/nl' => '/nl',
  ];

  /**
   * Redirects source to target for common page.
   *
   * @var string[]
   */
  protected array $pageRedirects = [
    // International common page.
    '/node/2' => '/test',
    '/test' => '/test',
    // Allowed language-country combinations.
    '/nl-be/node/2' => '/nl-be/test',
    '/fr-be/node/2' => '/fr-be/test',
    '/de-be/node/2' => '/de-be/test',
    '/en-us/node/2' => '/en-us/test',
    '/en-kr/node/2' => '/en-kr/test',
    '/nl-be/test' => '/nl-be/test',
    '/fr-be/test' => '/fr-be/test',
    '/de-be/test' => '/de-be/test',
    '/en-us/test' => '/en-us/test',
    '/en-kr/test' => '/en-kr/test',
    // Repairable language-country combinations.
    '/en-be/node/2' => '/nl-be/test',
    '/ko-kr/node/2' => '/en-kr/test',
    '/en-be/test' => '/nl-be/test',
    '/ko-kr/test' => '/en-kr/test',
    // Disabled language-country combinations.
    '/zh-cn/node/2' => '/test',
    '/en-eg/node/2' => '/test',
    '/fr-ma/node/2' => '/test',
    '/zh-cn/test' => '/test',
    '/en-eg/test' => '/test',
    '/fr-ma/test' => '/test',
    // Obscure language-country combination matching the pattern.
    '/xx-yy/node/2' => '/test',
    '/xx-yy/test' => '/test',
    // Language fallback for langcode prefix.
    '/de/node/2' => '/de/test',
    '/de/test' => '/de/test',
    '/fr/node/2' => '/fr/test',
    '/fr/test' => '/fr/test',
    '/nl/node/2' => '/nl/test',
    '/nl/test' => '/nl/test',
  ];

  /**
   * Redirects source to target for admin page.
   *
   * @var string[]
   */
  protected array $adminRedirects = [
    '/de-de/admin/content' => '/de/admin/content',
    '/admin/content' => '/admin/content',
    '/de/admin/content' => '/de/admin/content',
    '/fr/admin/content' => '/fr/admin/content',
    '/nl/admin/content' => '/nl/admin/content',
    '/fr-be/admin/content' => '/fr/admin/content',
    '/nl-be/admin/content' => '/nl/admin/content',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // The kernel test base removes the alias path processor.
    if ($container->hasDefinition('path_alias.path_processor')) {
      $container->getDefinition('path_alias.path_processor')
        ->addTag('path_processor_inbound', ['priority' => 100])
        ->addTag('path_processor_outbound', ['priority' => 300]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {

    parent::setUp();

    $this->installConfig(['system']);
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');

    // @todo Deprecated in Drupal 10.1 and changed in Drupal 11.
    // @phpstan-ignore-next-line
    $account = $this->createUser([], [
      'access content',
      'access administration pages',
    ]);
    $this->drupalSetCurrentUser($account);

    $this->setUpTerms($this->exampleWithContinents);

    $node_type = NodeType::create([
      'type' => 'page',
      'label' => 'Page',
    ]);
    $node_type->save();

    // Add node that represents the homepage.
    $homepage = $this->createNode([
      'title' => 'Hello, world!',
      'status' => TRUE,
    ]);

    $config = $this->config('system.site');
    $config->set('page.front', $homepage->toUrl()->toString());
    $config->save();

    // Add a common node that is used with path aliases.
    $page = $this->createNode([
      'title' => 'Test page!',
      'status' => TRUE,
    ]);
    $page->addTranslation('de', ['title' => 'Test page!']);
    $page->addTranslation('fr', ['title' => 'Test page!']);
    $page->addTranslation('nl', ['title' => 'Test page!']);
    $page->save();

    $this->createPathAlias('/node/' . $page->id(), '/test');

    // Configure language negotiation plugins.
    $config = $this->config('language.types');
    $config->set('configurable', [
      LanguageInterface::TYPE_URL,
      LanguageInterface::TYPE_INTERFACE,
      LanguageInterface::TYPE_CONTENT,
    ]);
    $config->set('negotiation.language_url.enabled', [
      LanguageNegotiationLanguageCountryUrl::METHOD_ID => -20,
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationUrlFallback::METHOD_ID => -18,
    ]);
    $config->set('negotiation.language_interface.enabled', [
      LanguageNegotiationLanguageCountryUrl::METHOD_ID => -20,
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 0,
    ]);
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationLanguageCountryUrl::METHOD_ID => -20,
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 0,
    ]);
    $config->save();
    $config = $this->config('language.negotiation');
    $config->set('url.source', LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $config->set('url.prefixes', [
      'en' => '',
      'de' => 'de',
      'fr' => 'fr',
      'nl' => 'nl',
      'zh-hans' => 'zh',
    ]);
    $config->save();

    // Container rebuild is required to activate language negotiation plugins.
    $this->container->get('kernel')->rebuildContainer();

    // The kernel test base invokes the Drupal kernel preHandle. Therefore, the
    // request stack needs to be popped in order to identify the correct master
    // request in the following.
    $this->container->get('request_stack')->pop();
  }

  /**
   * Tests redirects for homepage related paths.
   *
   * @dataProvider redirectsProvider
   */
  public function testRedirects(array $paths, string $title): void {
    foreach ($paths as $initial_path => $target_path) {
      $final_path = $this->getPathAfterRedirects($initial_path);
      $this->assertEquals($target_path, $final_path, sprintf('The requested path %s is redirected to the correct target path %s.', $initial_path, $target_path));
      self::assertStringContainsString($title, $this->getRawContent());
      $this->resetCurrentCountry();
    }
  }

  /**
   * Data provider for testHomepageRedirects.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function redirectsProvider(): array {

    $cases['home-page'] = [
      'paths' => $this->homeRedirects,
      'title' => 'Hello, world!',
    ];

    $cases['common-page'] = [
      'paths' => $this->pageRedirects,
      'title' => 'Test page!',
    ];

    $cases['admin-page'] = [
      'paths' => $this->adminRedirects,
      'title' => 'Content',
    ];

    return $cases;
  }

  /**
   * Gets the target path after maybe redirecting the response.
   *
   * @param string $current_path
   *   The initial path to request.
   *
   * @return string
   *   The final path after redirects.
   *
   * @throws \Exception
   */
  protected function getPathAfterRedirects(string $current_path): string {

    $redirect_count = 0;

    do {

      $request = Request::create($current_path);
      $response = $this->doRequest($request);

      if ($response instanceof LocalRedirectResponse) {
        $current_path = rtrim($response->getTargetUrl(), '/');
        $redirect_count++;
        continue;
      }

      if (!$response->isRedirection()) {

        // The HTML response may contain attachments that alters the target.
        if ($response instanceof AttachmentsInterface) {

          $path_info = $response->getAttachments()['drupalSettings']['path'] ?? NULL;
          $this->assertNotNull($path_info);
          $this->assertArrayHasKey('isFront', $path_info);

          // Mimic the JS behavior to resolve the front page.
          if ($path_info['isFront']) {
            $this->assertArrayHasKey('pathPrefix', $path_info);
            $current_path = '/' . rtrim($path_info['pathPrefix'], '/');
            break;
          }
        }

        // Otherwise, resolve the path from the current route match.
        $route_match = $this->container->get('current_route_match');
        if ($route_match instanceof StackedRouteMatchInterface) {
          $route_match = $route_match->getMasterRouteMatch();
        }
        $this->assertNotNull($route_match->getRouteName());
        $current_path = Url::fromRouteMatch($route_match)->toString();
        break;
      }

      $this->fail(sprintf('Unable to interpret response for %s path.', $current_path));

    } while ($redirect_count < 2);

    $this->assertTrue($redirect_count < 2, 'The request is redirected a limited amount of times (less than 2).');

    return $current_path;
  }

  /**
   * Resets the current country.
   */
  protected function resetCurrentCountry(): void {
    /** @var \Drupal\language_country_negotiation\Service\CurrentCountry $service */
    $service = $this->container->get('language_country_negotiation.current_country');
    $service->resetCountryCode();
  }

}
