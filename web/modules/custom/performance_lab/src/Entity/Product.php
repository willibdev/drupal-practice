<?php

declare(strict_types=1);

namespace Drupal\performance_lab\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\performance_lab\Form\ProductForm;
use Drupal\performance_lab\ProductAccessControlHandler;
use Drupal\performance_lab\ProductInterface;
use Drupal\performance_lab\ProductListBuilder;
use Drupal\performance_lab\ProductStorage;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the product entity class.
 */
#[ContentEntityType(
  id: 'performance_lab_product',
  label: new TranslatableMarkup('Product'),
  label_collection: new TranslatableMarkup('Products'),
  label_singular: new TranslatableMarkup('product'),
  label_plural: new TranslatableMarkup('products'),
  entity_keys: [
    'id' => 'id',
    'langcode' => 'langcode',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ProductListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ProductAccessControlHandler::class,
    'form' => [
      'add' => ProductForm::class,
      'edit' => ProductForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'storage' => ProductStorage::class,
  ],
  links: [
    'collection' => '/admin/content/product',
    'add-form' => '/product/add',
    'canonical' => '/product/{performance_lab_product}',
    'edit-form' => '/product/{performance_lab_product}/edit',
    'delete-form' => '/product/{performance_lab_product}/delete',
    'delete-multiple-form' => '/admin/content/product/delete-multiple',
  ],
  admin_permission: 'administer performance_lab_product',
  base_table: 'performance_lab_product',
  data_table: 'performance_lab_product_field_data',
  translatable: TRUE,
  label_count: [
    'singular' => '@count products',
    'plural' => '@count products',
  ],
  field_ui_base_route: 'entity.performance_lab_product.settings',
)]
class Product extends ContentEntityBase implements ProductInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Price'))
      ->setDescription(t('Decimal field to store the price of the product.'))
      ->setSettings([
        'precision' => 10,
        'scale' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_decimal',
        'label' => 'above',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the product was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the product was last edited.'));

    return $fields;
  }

}
