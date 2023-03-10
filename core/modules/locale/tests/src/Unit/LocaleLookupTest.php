<?php

/**
 * @file
 * Contains \Drupal\Tests\locale\Unit\LocaleLookupTest.
 */

namespace Drupal\Tests\locale\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\locale\LocaleLookup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\locale\LocaleLookup
 * @group locale
 */
class LocaleLookupTest extends UnitTestCase {

  /**
   * A mocked storage to use when instantiating LocaleTranslation objects.
   *
   * @var \Drupal\locale\StringStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * A mocked cache object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * A mocked lock object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  /**
   * A mocked user object built from AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $user;

  /**
   * A mocked config factory built with UnitTestCase::getConfigFactoryStub().
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockBuilder
   */
  protected $configFactory;

  /**
   * A mocked language manager built from LanguageManagerInterface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->storage = $this->getMock('Drupal\locale\StringStorageInterface');
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->lock->expects($this->never())
      ->method($this->anything());

    $this->user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->user->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(array('anonymous')));

    $this->configFactory = $this->getConfigFactoryStub(array('locale.settings' => array('cache_strings' => FALSE)));

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->requestStack = new RequestStack();

    $container = new ContainerBuilder();
    $container->set('current_user', $this->user);
    \Drupal::setContainer($container);
  }

  /**
   * Tests locale lookups without fallback.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMissWithoutFallback() {
    $args = array(
      'language' => 'en',
      'source' => 'test',
      'context' => 'irrelevant',
    );

    $result = (object) array(
      'translation' => 'test',
    );

    $this->cache->expects($this->once())
      ->method('get')
      ->with('locale:en:irrelevant:anonymous', FALSE);

    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->with($this->equalTo($args))
      ->will($this->returnValue($result));

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(array('en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack))
      ->setMethods(array('persist'))
      ->getMock();
    $locale_lookup->expects($this->never())
      ->method('persist');
    $this->assertSame('test', $locale_lookup->get('test'));
  }

  /**
   * Tests locale lookups with fallback.
   *
   * Note that context is irrelevant here. It is not used but it is required.
   *
   * @covers ::resolveCacheMiss
   *
   * @dataProvider resolveCacheMissWithFallbackProvider
   */
  public function testResolveCacheMissWithFallback($langcode, $string, $context, $expected) {
    // These are fake words!
    $translations = array(
      'en' => array(
        'test' => 'test',
        'fake' => 'fake',
        'missing pl' => 'missing pl',
        'missing cs' => 'missing cs',
        'missing both' => 'missing both',
      ),
      'pl' => array(
        'test' => 'test po polsku',
        'fake' => '??ciema',
        'missing cs' => 'zaginiony czech',
      ),
      'cs' => array(
        'test' => 'test v ??esk??',
        'fake' => 'fale??n??',
        'missing pl' => 'chyb??j??c?? pl',
      ),
    );
    $this->storage->expects($this->any())
      ->method('findTranslation')
      ->will($this->returnCallback(function ($argument) use ($translations) {
        if (isset($translations[$argument['language']][$argument['source']])) {
          return (object) array('translation' => $translations[$argument['language']][$argument['source']]);
        }
        return TRUE;
      }));

    $this->languageManager->expects($this->any())
      ->method('getFallbackCandidates')
      ->will($this->returnCallback(function (array $context = array()) {
        switch ($context['langcode']) {
          case 'pl':
            return array('cs', 'en');

          case 'cs':
            return array('en');

          default:
            return array();
        }
      }));

    $this->cache->expects($this->once())
      ->method('get')
      ->with('locale:' . $langcode . ':' . $context . ':anonymous', FALSE);

    $locale_lookup = new LocaleLookup($langcode, $context, $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack);
    $this->assertSame($expected, $locale_lookup->get($string));
  }

  /**
   * Provides test data for testResolveCacheMissWithFallback().
   */
  public function resolveCacheMissWithFallbackProvider() {
    return array(
      array('cs', 'test', 'irrelevant', 'test v ??esk??'),
      array('cs', 'fake', 'irrelevant', 'fale??n??'),
      array('cs', 'missing pl', 'irrelevant', 'chyb??j??c?? pl'),
      array('cs', 'missing cs', 'irrelevant', 'missing cs'),
      array('cs', 'missing both', 'irrelevant', 'missing both'),

      // Testing PL with fallback to cs, en.
      array('pl', 'test', 'irrelevant', 'test po polsku'),
      array('pl', 'fake', 'irrelevant', '??ciema'),
      array('pl', 'missing pl', 'irrelevant', 'chyb??j??c?? pl'),
      array('pl', 'missing cs', 'irrelevant', 'zaginiony czech'),
      array('pl', 'missing both', 'irrelevant', 'missing both'),
    );
  }

  /**
   * Tests locale lookups with persistent tracking.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMissWithPersist() {
    $args = array(
      'language' => 'en',
      'source' => 'test',
      'context' => 'irrelevant',
    );

    $result = (object) array(
      'translation' => 'test',
    );

    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->with($this->equalTo($args))
      ->will($this->returnValue($result));

    $this->configFactory = $this->getConfigFactoryStub(array('locale.settings' => array('cache_strings' => TRUE)));
    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(array('en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack))
      ->setMethods(array('persist'))
      ->getMock();
    $locale_lookup->expects($this->once())
      ->method('persist');

    $this->assertSame('test', $locale_lookup->get('test'));
  }

  /**
   * Tests locale lookups without a found translation.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMissNoTranslation() {
    $string = $this->getMock('Drupal\locale\StringInterface');
    $string->expects($this->once())
      ->method('addLocation')
      ->will($this->returnSelf());
    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->will($this->returnValue(NULL));
    $this->storage->expects($this->once())
      ->method('createString')
      ->will($this->returnValue($string));

    $request = Request::create('/test');
    $this->requestStack->push($request);

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(array('en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack))
      ->setMethods(array('persist'))
      ->getMock();
    $locale_lookup->expects($this->never())
      ->method('persist');

    $this->assertTrue($locale_lookup->get('test'));
  }

}
