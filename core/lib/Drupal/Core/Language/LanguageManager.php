<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class responsible for providing language support on language-unaware sites.
 */
class LanguageManager implements LanguageManagerInterface {
  use DependencySerializationTrait;

  /**
   * A static cache of translated language lists.
   *
   * Array of arrays to cache the result of self::getLanguages() keyed by the
   * language the list is translated to (first level) and the flags provided to
   * the method (second level).
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   *
   * @see \Drupal\Core\Language\LanguageManager::getLanguages()
   */
  protected $languages = array();

  /**
   * The default language object.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $defaultLanguage;

  /**
   * Constructs the language manager.
   *
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language.
   */
  public function __construct(LanguageDefault $default_language) {
    $this->defaultLanguage = $default_language;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultilingual() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageTypes() {
    return array(LanguageInterface::TYPE_INTERFACE, LanguageInterface::TYPE_CONTENT, LanguageInterface::TYPE_URL);
  }

  /**
   * Returns information about all defined language types.
   *
   * Defines the three core language types:
   * - Interface language is the only configurable language type in core. It is
   *   used by t() as the default language if none is specified.
   * - Content language is by default non-configurable and inherits the
   *   interface language negotiated value. It is used by the Field API to
   *   determine the display language for fields if no explicit value is
   *   specified.
   * - URL language is by default non-configurable and is determined through the
   *   URL language negotiation method or the URL fallback language negotiation
   *   method if no language can be detected. It is used by l() as the default
   *   language if none is specified.
   *
   * @return array
   *   An associative array of language type information arrays keyed by
   *   language type machine name, in the format of
   *   hook_language_types_info().
   */
  public function getDefinedLanguageTypesInfo() {
    $this->definedLanguageTypesInfo = array(
      LanguageInterface::TYPE_INTERFACE => array(
        'name' => new TranslatableMarkup('Interface text'),
        'description' => new TranslatableMarkup('Order of language detection methods for interface text. If a translation of interface text is available in the detected language, it will be displayed.'),
        'locked' => TRUE,
      ),
      LanguageInterface::TYPE_CONTENT => array(
        'name' => new TranslatableMarkup('Content'),
        'description' => new TranslatableMarkup('Order of language detection methods for content. If a version of content is available in the detected language, it will be displayed.'),
        'locked' => TRUE,
      ),
      LanguageInterface::TYPE_URL => array(
        'locked' => TRUE,
      ),
    );

    return $this->definedLanguageTypesInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguage($type = LanguageInterface::TYPE_INTERFACE) {
    return $this->getDefaultLanguage();
  }

  /**
   * {@inheritdoc}
   */
  public function reset($type = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLanguage() {
    return $this->defaultLanguage->get();
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages($flags = LanguageInterface::STATE_CONFIGURABLE) {
    $static_cache_id = $this->getCurrentLanguage()->getId();
    if (!isset($this->languages[$static_cache_id][$flags])) {
      // If this language manager is used, there are no configured languages.
      // The default language and locked languages comprise the full language
      // list.
      $default = $this->getDefaultLanguage();
      $languages = array($default->getId() => $default);
      $languages += $this->getDefaultLockedLanguages($default->getWeight());

      // Filter the full list of languages based on the value of $flags.
      $this->languages[$static_cache_id][$flags] = $this->filterLanguages($languages, $flags);
    }
    return $this->languages[$static_cache_id][$flags];
  }

  /**
   * {@inheritdoc}
   */
  public function getNativeLanguages() {
    // In a language unaware site we don't have translated languages.
    return $this->getLanguages();
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage($langcode) {
    $languages = $this->getLanguages(LanguageInterface::STATE_ALL);
    return isset($languages[$langcode]) ? $languages[$langcode] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageName($langcode) {
    if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      return new TranslatableMarkup('None');
    }
    if ($language = $this->getLanguage($langcode)) {
      return $language->getName();
    }
    if (empty($langcode)) {
      return new TranslatableMarkup('Unknown');
    }
    return new TranslatableMarkup('Unknown (@langcode)', array('@langcode' => $langcode));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLockedLanguages($weight = 0) {
    $languages = array();

    $locked_language = array(
      'default' => FALSE,
      'locked' => TRUE,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    // This is called very early while initializing the language system. Prevent
    // early t() calls by using the TranslatableMarkup.
    $languages[LanguageInterface::LANGCODE_NOT_SPECIFIED] = new Language(array(
      'id' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'name' => new TranslatableMarkup('Not specified'),
      'weight' => ++$weight,
    ) + $locked_language);

    $languages[LanguageInterface::LANGCODE_NOT_APPLICABLE] = new Language(array(
      'id' => LanguageInterface::LANGCODE_NOT_APPLICABLE,
      'name' => new TranslatableMarkup('Not applicable'),
      'weight' => ++$weight,
    ) + $locked_language);

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function isLanguageLocked($langcode) {
    $language = $this->getLanguage($langcode);
    return ($language ? $language->isLocked() : FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackCandidates(array $context = array()) {
    return array(LanguageInterface::LANGCODE_DEFAULT);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks($type, Url $url) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function getStandardLanguageList() {
    // This list is based on languages available from localize.drupal.org. See
    // http://localize.drupal.org/issues for information on how to add languages
    // there.
    //
    // The "Left-to-right marker" comments and the enclosed UTF-8 markers are to
    // make otherwise strange looking PHP syntax natural (to not be displayed in
    // right to left). See https://www.drupal.org/node/128866#comment-528929.
    return array(
      'af' => array('Afrikaans', 'Afrikaans'),
      'am' => array('Amharic', '????????????'),
      'ar' => array('Arabic', /* Left-to-right marker "???" */ '??????????????', LanguageInterface::DIRECTION_RTL),
      'ast' => array('Asturian', 'Asturianu'),
      'az' => array('Azerbaijani', 'Az??rbaycanca'),
      'be' => array('Belarusian', '????????????????????'),
      'bg' => array('Bulgarian', '??????????????????'),
      'bn' => array('Bengali', '???????????????'),
      'bo' => array('Tibetan', '????????????????????????'),
      'bs' => array('Bosnian', 'Bosanski'),
      'ca' => array('Catalan', 'Catal??'),
      'cs' => array('Czech', '??e??tina'),
      'cy' => array('Welsh', 'Cymraeg'),
      'da' => array('Danish', 'Dansk'),
      'de' => array('German', 'Deutsch'),
      'dz' => array('Dzongkha', '??????????????????'),
      'el' => array('Greek', '????????????????'),
      'en' => array('English', 'English'),
      'en-x-simple' => array('Simple English', 'Simple English'),
      'eo' => array('Esperanto', 'Esperanto'),
      'es' => array('Spanish', 'Espa??ol'),
      'et' => array('Estonian', 'Eesti'),
      'eu' => array('Basque', 'Euskera'),
      'fa' => array('Persian, Farsi', /* Left-to-right marker "???" */ '??????????', LanguageInterface::DIRECTION_RTL),
      'fi' => array('Finnish', 'Suomi'),
      'fil' => array('Filipino', 'Filipino'),
      'fo' => array('Faeroese', 'F??royskt'),
      'fr' => array('French', 'Fran??ais'),
      'fy' => array('Frisian, Western', 'Frysk'),
      'ga' => array('Irish', 'Gaeilge'),
      'gd' => array('Scots Gaelic', 'G??idhlig'),
      'gl' => array('Galician', 'Galego'),
      'gsw-berne' => array('Swiss German', 'Schwyzerd??tsch'),
      'gu' => array('Gujarati', '?????????????????????'),
      'he' => array('Hebrew', /* Left-to-right marker "???" */ '??????????', LanguageInterface::DIRECTION_RTL),
      'hi' => array('Hindi', '??????????????????'),
      'hr' => array('Croatian', 'Hrvatski'),
      'ht' => array('Haitian Creole', 'Krey??l ayisyen'),
      'hu' => array('Hungarian', 'Magyar'),
      'hy' => array('Armenian', '??????????????'),
      'id' => array('Indonesian', 'Bahasa Indonesia'),
      'is' => array('Icelandic', '??slenska'),
      'it' => array('Italian', 'Italiano'),
      'ja' => array('Japanese', '?????????'),
      'jv' => array('Javanese', 'Basa Java'),
      'ka' => array('Georgian', '????????????????????? ?????????'),
      'kk' => array('Kazakh', '??????????'),
      'km' => array('Khmer', '???????????????????????????'),
      'kn' => array('Kannada', '???????????????'),
      'ko' => array('Korean', '?????????'),
      'ku' => array('Kurdish', 'Kurd??'),
      'ky' => array('Kyrgyz', '????????????????'),
      'lo' => array('Lao', '?????????????????????'),
      'lt' => array('Lithuanian', 'Lietuvi??'),
      'lv' => array('Latvian', 'Latvie??u'),
      'mg' => array('Malagasy', 'Malagasy'),
      'mk' => array('Macedonian', '????????????????????'),
      'ml' => array('Malayalam', '??????????????????'),
      'mn' => array('Mongolian', '????????????'),
      'mr' => array('Marathi', '???????????????'),
      'ms' => array('Bahasa Malaysia', '???????? ??????????'),
      'my' => array('Burmese', '?????????????????????'),
      'ne' => array('Nepali', '??????????????????'),
      'nl' => array('Dutch', 'Nederlands'),
      'nb' => array('Norwegian Bokm??l', 'Norsk, bokm??l'),
      'nn' => array('Norwegian Nynorsk', 'Norsk, nynorsk'),
      'oc' => array('Occitan', 'Occitan'),
      'pa' => array('Punjabi', '??????????????????'),
      'pl' => array('Polish', 'Polski'),
      'pt-pt' => array('Portuguese, Portugal', 'Portugu??s, Portugal'),
      'pt-br' => array('Portuguese, Brazil', 'Portugu??s, Brasil'),
      'ro' => array('Romanian', 'Rom??n??'),
      'ru' => array('Russian', '??????????????'),
      'sco' => array('Scots', 'Scots'),
      'se' => array('Northern Sami', 'S??mi'),
      'si' => array('Sinhala', '???????????????'),
      'sk' => array('Slovak', 'Sloven??ina'),
      'sl' => array('Slovenian', 'Sloven????ina'),
      'sq' => array('Albanian', 'Shqip'),
      'sr' => array('Serbian', '????????????'),
      'sv' => array('Swedish', 'Svenska'),
      'sw' => array('Swahili', 'Kiswahili'),
      'ta' => array('Tamil', '???????????????'),
      'ta-lk' => array('Tamil, Sri Lanka', '???????????????, ??????????????????'),
      'te' => array('Telugu', '??????????????????'),
      'th' => array('Thai', '?????????????????????'),
      'tr' => array('Turkish', 'T??rk??e'),
      'tyv' => array('Tuvan', '???????? ??????'),
      'ug' => array('Uyghur', '??????????'),
      'uk' => array('Ukrainian', '????????????????????'),
      'ur' => array('Urdu', /* Left-to-right marker "???" */ '????????', LanguageInterface::DIRECTION_RTL),
      'vi' => array('Vietnamese', 'Ti???ng Vi???t'),
      'xx-lolspeak' => array('Lolspeak', 'Lolspeak'),
      'zh-hans' => array('Chinese, Simplified', '????????????'),
      'zh-hant' => array('Chinese, Traditional', '????????????'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * This function is a noop since the configuration cannot be overridden by
   * language unless the Language module is enabled. That replaces the default
   * language manager with a configurable language manager.
   *
   * @see \Drupal\language\ConfigurableLanguageManager::setConfigOverrideLanguage()
   */
  public function setConfigOverrideLanguage(LanguageInterface $language = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigOverrideLanguage() {
    return $this->getCurrentLanguage();
  }

  /**
   * Filters the full list of languages based on the value of the flag.
   *
   * The locked languages are removed by default.
   *
   * @param \Drupal\Core\Language\LanguageInterface[] $languages
   *    Array with languages to be filtered.
   * @param int $flags
   *   (optional) Specifies the state of the languages that have to be returned.
   *   It can be: LanguageInterface::STATE_CONFIGURABLE,
   *   LanguageInterface::STATE_LOCKED, or LanguageInterface::STATE_ALL.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An associative array of languages, keyed by the language code.
   */
  protected function filterLanguages(array $languages, $flags = LanguageInterface::STATE_CONFIGURABLE) {
    // STATE_ALL means we don't actually filter, so skip the rest of the method.
    if ($flags == LanguageInterface::STATE_ALL) {
      return $languages;
    }

    $filtered_languages = array();
    // Add the site's default language if requested.
    if ($flags & LanguageInterface::STATE_SITE_DEFAULT) {

      // Setup a language to have the defaults with data appropriate of the
      // default language only for runtime.
      $defaultLanguage = $this->getDefaultLanguage();
      $default = new Language(
        array(
          'id' => $defaultLanguage->getId(),
          'name' => new TranslatableMarkup("Site's default language (@lang_name)",
            array('@lang_name' => $defaultLanguage->getName())),
          'direction' => $defaultLanguage->getDirection(),
          'weight' => $defaultLanguage->getWeight(),
        )
      );
      $filtered_languages[LanguageInterface::LANGCODE_SITE_DEFAULT] = $default;
    }

    foreach ($languages as $id => $language) {
      if (($language->isLocked() && ($flags & LanguageInterface::STATE_LOCKED)) || (!$language->isLocked() && ($flags & LanguageInterface::STATE_CONFIGURABLE))) {
        $filtered_languages[$id] = $language;
      }
    }

    return $filtered_languages;
  }

}
