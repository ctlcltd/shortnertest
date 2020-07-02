/*
 * backend/init.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

function init() {
  var tc_value = '1; ';
  var tc_path = 'path=' + bepath + '; ';
  var tc_secured = ''; //'secure; ';
  var tc_expires = 'expires=' + new Date(new Date().getTime() + (60 * 1e3)).toGMTString() + '; ';

  document.cookie = 'urls-test=' + tc_value + tc_path + tc_expires + tc_secured + 'samesite=strict';

  if (document.cookie.indexOf('urls-test') === -1) {
    return '';
  }

  if (document.cookie.indexOf('urls-sign') != -1) {
    nav();
    route();
  } else {
    signin();
  }
}

init();
