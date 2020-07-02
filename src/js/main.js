/*
 * backend/main.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

function main() {
  const view = document.getElementById('main');
  // const menu = view.querySelector('.nav.placeholder');
  const menu = view.querySelector('.nav');

  nav(menu);

  view.removeAttribute('hidden');
}
