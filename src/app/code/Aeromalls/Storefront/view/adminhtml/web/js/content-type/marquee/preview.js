define([
 'Magento_PageBuilder/js/content-type/preview-collection',
 'Magento_PageBuilder/js/content-type-menu/option',
 'Magento_PageBuilder/js/content-type-factory',
 'Magento_PageBuilder/js/config',
 'mage/translate'
], function (PreviewCollection, Option, factory, config, translate) {
 'use strict';
 function Preview() { PreviewCollection.apply(this, arguments); }
 Preview.prototype = Object.create(PreviewCollection.prototype);
 Preview.prototype.constructor = Preview;
 Preview.prototype.retrieveOptions = function () {
  var options = PreviewCollection.prototype.retrieveOptions.call(this);
  options.add = new Option({
   preview: this,
   icon: '<i class="icon-pagebuilder-add"></i>',
   title: translate('Add Logo'),
   action: this.addLogo,
   classes: ['add-child'],
   sort: 10
  });
  return options;
 };
 Preview.prototype.addLogo = function () {
  var self = this;
  return factory(config.getContentTypeConfig('image'), this.contentType, this.contentType.stageId, {})
   .then(function (image) { self.contentType.addChild(image); return image; });
 };
 return Preview;
});
