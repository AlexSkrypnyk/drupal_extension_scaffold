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
   * Constructs a ForceCrystalForm instance.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    /**
     * The Force Crystal service.
     */
    protected ForceCrystalService $yourExtensionService,
  ) {
    // @phpstan-ignore-next-line
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
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
  #[\Override]
  protected function getEditableConfigNames(): array {
    return ['force_crystal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFormId(): string {
    return 'force_crystal_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
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
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('force_crystal.settings');
    $config->set('text', $form_state->getValue('text'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
