/*
 * backend/nav.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

function nav(menu) {
  // if (benav && menu) {
  //   const nav = benav.cloneNode(true);

  //   menu.replaceWith(benav);

  //   return benav;
  // } else if (! menu) {
  //   return benav;
  // }

  const nav = document.getElementById('nav').cloneNode(true);
  const nav_items = nav.querySelectorAll('a');

  function click(evt) {
    evt.preventDefault();

    route(this.href);

    return false;
  }

  for (const el of nav_items) {
    el.href = bepath + '/' + el.getAttribute('href');
    el.onclick = click;
  }

  nav.removeAttribute('id');
  nav.removeAttribute('hidden');

  benav = nav;

  if (menu) {
    const nav = benav.cloneNode(true);

    menu.replaceWith(benav);
  }

  return benav;
}
