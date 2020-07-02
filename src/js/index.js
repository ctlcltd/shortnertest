/*!
 * backend/script.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

const bepath = '/backend';
const beroutes = {
  '' : { '': main, 'login': signin, 'logout': signout },
  'store': { '': view_list, 'add': view_edit, 'edit': view_edit },
  'domains': { '': view_list, 'add': view_edit, 'edit': view_edit },
  'users': { '': view_list, 'add': view_edit, 'edit': view_edit },
  'test': { '': api_test }
};
let benav;
const apipath = '/api';
