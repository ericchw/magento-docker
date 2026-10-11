define(['Magento_PageBuilder/js/utils/object'], function (object) {
 'use strict';
 function Duration() {}
 Duration.prototype.fromDom = function (value) { return String(parseFloat(value) || 20); };
 Duration.prototype.toDom = function (name, data) {
  var seconds = parseFloat(object.get(data, name));
  return Math.min(120, Math.max(5, isFinite(seconds) ? seconds : 20)) + 's';
 };
 return Duration;
});
