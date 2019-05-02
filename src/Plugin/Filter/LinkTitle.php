<?php
/**
 * @file
 * Contains Drupal\helloworld\Plugin\Filter\LinkTitle.
 */
namespace Drupal\linktitle\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;


/**
 * @Filter(
 *   id = "link_title",
 *   title = @Translation("Adds a title attribute to links."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "linktitle_display_tip" = 1,
 *     "linktitle_maxread_bytes" = 2000,
 *     "linktitle_timeout" = 1000
 *   }
 * )
 */
class LinkTitle extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['linktitle_maxread_bytes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum number of bytes read'),
      '#default_value' => $this->settings['linktitle_maxread_bytes'],
      '#maxlength' => 1024,
      '#size' => 250,
    ];

    $form['linktitle_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum time to connect to the page that is referred to.'),
      '#default_value' => $this->settings['linktitle_timeout'],
      '#maxlength' => 1024,
      '#size' => 250,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    global $_linktitle_setting_maxread;
    global $_linktitle_setting_timeout;
    $_linktitle_setting_maxread = $this->settings['linktitle_maxread_bytes'];
    $_linktitle_setting_timeout = $this->settings['linktitle_timeout'];
    $pattern = '%<a([^>]*?href="([^"]+?)"[^>]*?)>%i';

    $result = new FilterProcessResult($text);
    $text = preg_replace_callback($pattern, array($this, '_linktitle_filter_text_process'), $text);
    $result->setProcessedText($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($this->settings['linktitle_display_tip']) {
      return t('Adds a title attribute to links found in the content.');
    }
    return t('Adds a title attribute to links found in the content.');
  }

  /**
   * Callback for linktitle_get_remotetitle().
   */
  public function linktitle_get_remotetitle($url, $bytes = 5000) {
    $innertitle = '';
    $dat = '';
    $fp = $this->file_get_contents_curl($url);
    if ($fp) {
      // if the url is not available $dat wil be empty
      if (preg_match('|<title>[[:space:]]*(.*)[[:space:]]*</title>|Uis', $fp, $match )) {
        $innertitle = $match[1];
      }
    }
    return $innertitle;
  }

  /**
   * Callback for _linktitle_filter_process().
   */
  public function _linktitle_filter_text_process($matches) {
    global $_linktitle_setting_maxread;
    if (strpos($matches[1], 'title=') == FALSE) {
      $pagetitle = $this->linktitle_get_remotetitle($matches[2], $_linktitle_setting_maxread);
      if (empty($pagetitle)) {
        return $matches[0];
      }
      else {
        // make sure the inserted page title is plain text
        // $insert_title = 'title="'.check_plain($pagetitle).'"';
        $insert_title = 'title="' . $pagetitle . '"';
        return substr_replace($matches[0], $insert_title, -1, -1);
      }
    }
    else {
      return $matches[0];
    }
  }

  /**
   * Callback for file_get_contents_curl().
   */
  public function file_get_contents_curl($url)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
  }
}

