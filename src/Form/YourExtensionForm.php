<?php

declare(strict_types=1);

namespace Drupal\your_extension\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\your_extension\YourExtensionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Your Extension.
 */
class YourExtensionForm extends ConfigFormBase {

  /**
   * The Your Extension service.
   *
   * @var \Drupal\your_extension\YourExtensionService
   */
  protected YourExtensionService $yourExtensionService;

  /**
   * GeneratedContentForm constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    YourExtensionService $your_extension_service,
  ) {
    // @phpstan-ignore-next-line
    parent::__construct($config_factory, $typedConfigManager);
    $this->yourExtensionService = $your_extension_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): YourExtensionForm {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('your_extension.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['your_extension.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'your_extension_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('your_extension.settings');

    $form['text'] = [
      '#title' => $this->t('Text for <code>noscript</code> Tag'),
      '#type' => 'textarea',
      '#description' => $this->t('Enter the text to be included in the <code>noscript</code> tag.'),
      '#default_value' => $config->get('text'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('your_extension.settings');
    $config->set('text', $form_state->getValue('text'));
    $config->save();

    parent::submitForm($form, $form_state);

    drupal_flush_all_caches();
  }

}
