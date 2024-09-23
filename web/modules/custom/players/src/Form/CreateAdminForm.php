<?php

namespace Drupal\players\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CreateAdminForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CreateAdminForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'players_create_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#options' => [
        'federation_admin' => $this->t('Federation Admin'),
        'competition_admin' => $this->t('Competition Admin'),
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateFederationField',
        'wrapper' => 'federation-wrapper',
      ],
    ];

    $form['federation_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'federation-wrapper'],
    ];

    $selected_role = $form_state->getValue('role');
    if ($selected_role == 'federation_admin' ) {
      $form['federation_wrapper']['federation'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Federation'),
        '#target_type' => 'taxonomy_term',
        '#selection_settings' => ['target_bundles' => ['federation']],
        '#required' => TRUE,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create User'),
    ];

    return $form;
  }

  /**
   * Ajax callback to update the federation field.
   */
  public function updateFederationField(array &$form, FormStateInterface $form_state) {
    return $form['federation_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('role') == 'federation_admin' && empty($form_state->getValue('federation'))) {
      $form_state->setErrorByName('federation', $this->t('Federation is required for Federation role.'));
    }

    // Check if username already exists
    $existing_user = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['name' => $form_state->getValue('username')]);
    if (!empty($existing_user)) {
      $form_state->setErrorByName('username', $this->t('This username is already taken.'));
    }

    // Check if email already exists
    $existing_email = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $form_state->getValue('email')]);
    if (!empty($existing_email)) {
      $form_state->setErrorByName('email', $this->t('This email address is already registered.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::create();
    $user->setUsername($form_state->getValue('username'));
    $user->setEmail($form_state->getValue('email'));
    $user->addRole($form_state->getValue('role'));
    $user->activate();

    // Set federation if applicable
    if ($form_state->getValue('role') == 'federation_admin' && $form_state->getValue('federation')) {
      $user->set('field_admin_federation', $form_state->getValue('federation'));
    }

    $user->save();

    $this->messenger()->addMessage($this->t('User @username has been created.', ['@username' => $user->getAccountName()]));
    $form_state->setRedirect('entity.user.collection');
  }
}
