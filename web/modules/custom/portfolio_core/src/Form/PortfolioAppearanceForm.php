<?php

namespace Drupal\portfolio_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Drupal Admin settings form for Portfolio Typography & Link Fonts.
 */
class PortfolioAppearanceForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['portfolio_core.appearance'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'portfolio_core_appearance_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('portfolio_core.appearance');

    $form['link_font'] = [
      '#type' => 'select',
      '#title' => $this->t('Links & Small Labels Font'),
      '#description' => $this->t('Controls the typeface used for small links (LinkedIn, GitHub, Read case study, View all), eyebrows, and tags across the portfolio.'),
      '#options' => [
        'jakarta' => $this->t('Plus Jakarta Sans (Humanist Grotesque — Recommended)'),
        'inter' => $this->t('Inter (Neutral Swiss Sans)'),
        'dm_sans' => $this->t('DM Sans (Editorial Geometric Sans)'),
        'serif' => $this->t('Instrument Serif (Editorial Serif)'),
      ],
      '#default_value' => $config->get('link_font') ?: 'jakarta',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('portfolio_core.appearance')
      ->set('link_font', $form_state->getValue('link_font'))
      ->save();

    try {
      \Drupal::service('portfolio_core.revalidation_notifier')->notify(['cms', 'bootstrap']);
    }
    catch (\Throwable $e) {
      // Non-blocking.
    }

    parent::submitForm($form, $form_state);
  }

}
