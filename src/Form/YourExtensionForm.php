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
   * Constructs a YourExtensionForm instance.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    /**
     * The Your Extension service.
     */
    protected YourExtensionService $yourExtensionService,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
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
  protected function getEditableConfigNames(): array {
    return ['your_extension.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'your_extension_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('your_extension.settings');
    $config->set('text', $form_state->getValue('text'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
