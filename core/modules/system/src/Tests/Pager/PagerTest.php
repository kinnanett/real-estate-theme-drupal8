<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Pager\PagerTest.
 */

namespace Drupal\system\Tests\Pager;

use Drupal\simpletest\WebTestBase;

/**
 * Tests pager functionality.
 *
 * @group Pager
 */
class PagerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'pager_test');

  /**
   * A user with permission to access site reports.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected $profile = 'testing';

  protected function setUp() {
    parent::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->adminUser = $this->drupalCreateUser(array(
      'access site reports',
    ));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests markup and CSS classes of pager links.
   */
  function testActiveClass() {
    // Verify first page.
    $this->drupalGet('admin/reports/dblog');
    $current_page = 0;
    $this->assertPagerItems($current_page);

    // Verify any page but first/last.
    $current_page++;
    $this->drupalGet('admin/reports/dblog', array('query' => array('page' => $current_page)));
    $this->assertPagerItems($current_page);

    // Verify last page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager__item--last'));
    preg_match('@page=(\d+)@', $elements[0]['href'], $matches);
    $current_page = (int) $matches[1];
    $this->drupalGet($GLOBALS['base_root'] . parse_url($this->getUrl())['path'] . $elements[0]['href'], array('external' => TRUE));
    $this->assertPagerItems($current_page);
  }

  /**
   * Test proper functioning of the query parameters and the pager cache context.
   */
  protected function testPagerQueryParametersAndCacheContext() {
    // First page.
    $this->drupalGet('pager-test/query-parameters');
    $this->assertText(t('Pager calls: 0'), 'Initial call to pager shows 0 calls.');
    $this->assertText('[url.query_args.pagers:0]=0.0');
    $this->assertCacheContext('url.query_args');

    // Go to last page, the count of pager calls need to go to 1.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager__item--last'));
    $this->drupalGet($this->getAbsoluteUrl($elements[0]['href']));
    $this->assertText(t('Pager calls: 1'), 'First link call to pager shows 1 calls.');
    $this->assertText('[url.query_args.pagers:0]=0.60');
    $this->assertCacheContext('url.query_args');

    // Go back to first page, the count of pager calls need to go to 2.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager__item--first'));
    $this->drupalGet($this->getAbsoluteUrl($elements[0]['href']));
    $this->drupalGet($GLOBALS['base_root'] . parse_url($this->getUrl())['path'] . $elements[0]['href'], array('external' => TRUE));
    $this->assertText(t('Pager calls: 2'), 'Second link call to pager shows 2 calls.');
    $this->assertText('[url.query_args.pagers:0]=0.0');
    $this->assertCacheContext('url.query_args');
  }

  /**
   * Test proper functioning of the ellipsis.
   */
  public function testPagerEllipsis() {
    // Insert 100 extra log messages to get 9 pages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 100; $i++) {
      $logger->debug($this->randomString());
    }
    $this->drupalGet('admin/reports/dblog');
    $elements = $this->cssSelect(".pager__item--ellipsis:contains('???')");
    $this->assertEqual(count($elements), 0, 'No ellipsis has been set.');

    // Insert an extra 50 log messages to get 10 pages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 50; $i++) {
      $logger->debug($this->randomString());
    }
    $this->drupalGet('admin/reports/dblog');
    $elements = $this->cssSelect(".pager__item--ellipsis:contains('???')");
    $this->assertEqual(count($elements), 1, 'Found the ellipsis.');
  }

  /**
   * Asserts pager items and links.
   *
   * @param int $current_page
   *   The current pager page the internal browser is on.
   */
  protected function assertPagerItems($current_page) {
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', array(':class' => 'pager__items'));
    $this->assertTrue(!empty($elements), 'Pager found.');

    // Make current page 1-based.
    $current_page++;

    // Extract first/previous and next/last items.
    // first/previous only exist, if the current page is not the first.
    if ($current_page > 1) {
      $first = array_shift($elements);
      $previous = array_shift($elements);
    }
    // next/last always exist, unless the current page is the last.
    if ($current_page != count($elements)) {
      $last = array_pop($elements);
      $next = array_pop($elements);
    }

    // We remove elements from the $elements array in the following code, so
    // we store the total number of pages for verifying the "last" link.
    $total_pages = count($elements);

    // Verify items and links to pages.
    foreach ($elements as $page => $element) {
      // Make item/page index 1-based.
      $page++;

      if ($current_page == $page) {
        $this->assertClass($element, 'is-active', 'Element for current page has .is-active class.');
        $this->assertTrue($element->a, 'Element for current page has link.');
        $destination = $element->a['href'][0]->__toString();
        // URL query string param is 0-indexed.
        $this->assertEqual($destination, '?page=' . ($page - 1));
      }
      else {
        $this->assertNoClass($element, 'is-active', "Element for page $page has no .is-active class.");
        $this->assertClass($element, 'pager__item', "Element for page $page has .pager__item class.");
        $this->assertTrue($element->a, "Link to page $page found.");
        $destination = $element->a['href'][0]->__toString();
        $this->assertEqual($destination, '?page=' . ($page - 1));
      }
      unset($elements[--$page]);
    }
    // Verify that no other items remain untested.
    $this->assertTrue(empty($elements), 'All expected items found.');

    // Verify first/previous and next/last items and links.
    if (isset($first)) {
      $this->assertClass($first, 'pager__item--first', 'Element for first page has .pager__item--first class.');
      $this->assertTrue($first->a, 'Link to first page found.');
      $this->assertNoClass($first->a, 'is-active', 'Link to first page is not active.');
      $destination = $first->a['href'][0]->__toString();
      $this->assertEqual($destination, '?page=0');
    }
    if (isset($previous)) {
      $this->assertClass($previous, 'pager__item--previous', 'Element for first page has .pager__item--previous class.');
      $this->assertTrue($previous->a, 'Link to previous page found.');
      $this->assertNoClass($previous->a, 'is-active', 'Link to previous page is not active.');
      $destination = $previous->a['href'][0]->__toString();
      // URL query string param is 0-indexed, $current_page is 1-indexed.
      $this->assertEqual($destination, '?page=' . ($current_page - 2));
    }
    if (isset($next)) {
      $this->assertClass($next, 'pager__item--next', 'Element for next page has .pager__item--next class.');
      $this->assertTrue($next->a, 'Link to next page found.');
      $this->assertNoClass($next->a, 'is-active', 'Link to next page is not active.');
      $destination = $next->a['href'][0]->__toString();
      // URL query string param is 0-indexed, $current_page is 1-indexed.
      $this->assertEqual($destination, '?page=' . $current_page);
    }
    if (isset($last)) {
      $this->assertClass($last, 'pager__item--last', 'Element for last page has .pager__item--last class.');
      $this->assertTrue($last->a, 'Link to last page found.');
      $this->assertNoClass($last->a, 'is-active', 'Link to last page is not active.');
      $destination = $last->a['href'][0]->__toString();
      // URL query string param is 0-indexed.
      $this->assertEqual($destination, '?page=' . ($total_pages - 1));
    }
  }

  /**
   * Asserts that an element has a given class.
   *
   * @param \SimpleXMLElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   */
  protected function assertClass(\SimpleXMLElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class found.";
    }
    $this->assertTrue(strpos($element['class'], $class) !== FALSE, $message);
  }

  /**
   * Asserts that an element does not have a given class.
   *
   * @param \SimpleXMLElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   */
  protected function assertNoClass(\SimpleXMLElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class not found.";
    }
    $this->assertTrue(strpos($element['class'], $class) === FALSE, $message);
  }
}
