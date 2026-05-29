<?php

declare(strict_types=1);

namespace Drupal\force_crystal\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\force_crystal\ForceCrystalService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Force Crystal.
 */
class ForceCrystalForm extends ConfigFormBase {

  /**
   * The Force Crystal service.
   *
   * @var \Drupal\force_crystal\ForceCrystalService
   */
  protected ForceCrystalService $yourExtensionService;

  /**
   * Constructs a ForceCrystalForm instance.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    ForceCrystalService $force_crystal_service,
  ) {
    // @phpstan-ignore-next-line
    parent::__construct($config_factory, $typedConfigManager);
    $this->yourExtensionService = $force_crystal_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ForceCrystalForm {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('force_crystal.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['force_crystal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'force_crystal_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('force_crystal.settings');

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
    $config = $this->config('force_crystal.settings');
    $config->set('text', $form_state->getValue('text'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
