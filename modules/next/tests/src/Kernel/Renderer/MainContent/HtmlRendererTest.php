<?php

namespace Drupal\Tests\next\Kernel\Renderer\MainContent;

use Drupal\KernelTests\KernelTestBase;
use Drupal\next\Entity\NextEntityTypeConfig;
use Drupal\next\Entity\NextSite;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the HTMLRenderer for preview.
 *
 * @coversDefaultClass \Drupal\next\Render\MainContent\HtmlRenderer
 *
 * @group next
 */
class HtmlRendererTest extends KernelTestBase {

  use NodeCreationTrait, UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter', 'next', 'node', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['filter', 'next', 'system', 'user']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);

    // Create page type.
    $page_type = NodeType::create([
      'type' => 'page',
      'label' => 'Page',
    ]);
    $page_type->save();

    $article_type = NodeType::create([
      'type' => 'article',
      'label' => 'Article',
    ]);
    $article_type->save();

    // Create NextSite entities.
    $blog = NextSite::create([
      'label' => 'Blog',
      'id' => 'blog',
      'base_url' => 'https://blog.com',
      'preview_url' => 'https://blog.com/api/preview',
      'preview_secret' => 'one'
    ]);
    $blog->save();

    // Create entity type config.
    $entity_type_config = NextEntityTypeConfig::create([
      'id' => 'node.page',
      'site_resolver' => 'site_selector',
      'configuration' => [
        'sites' => [
          'blog' => 'blog',
        ],
      ],
    ]);
    $entity_type_config->save();
  }

  /**
   * @covers ::prepare
   */
  public function testPrepare() {
    $this->setCurrentUser($this->createUser(['bypass node access']));

    $page = $this->createNode(['type' => 'page']);
    $request = Request::create($page->toUrl()->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    $preview_url = 'https://blog.com/api/preview?secret=one&slug=/node/1&locale=en&defaultLocale=en&resourceVersion=rel%3Alatest-version';
    $this->assertFieldByXPath('//iframe[@src="' . $preview_url . '"]');

    $article = $this->createNode(['type' => 'article']);
    $request = Request::create($article->toUrl()->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    $preview_url = 'https://blog.com/api/preview?secret=one&slug=/node/2&locale=en&defaultLocale=en&resourceVersion=rel%3Alatest-version';
    $this->assertNoFieldByXPath('//iframe[@src="' . $preview_url . '"]');
  }

}
