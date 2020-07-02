/*
 * backend/route.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

function route(href, title) {
  const views = document.querySelectorAll('main');
  const hists = href ? true : false;

  href = href ? href : window.location.href;
  title = title ? title : document.title;

  if (href.indexOf(bepath) === -1) {
    throw 'Wrong backend path';
  }

  const url = href.replace(window.location.protocol + '//' + window.location.host, '');
  const path = url.split('?');
  const uri = path[0].split('/')[2];
  const qs = path[1] ? path[1].split('&') : '';
  const key = qs[0] ? qs[0] : '';
  const value = qs[1] ? qs[1] : '';

  console.info('route()', { path, uri, qs, key, value });

  for (const view of views) {
    if (view.cloned) {
      view.remove();
    }

    view.setAttribute('hidden', '');
  }

  if (uri != undefined && uri in beroutes === false) {
    throw 'Wrong URI Route';
  }
  if (key != undefined && key in beroutes[uri] === false) {
    throw 'Wrong QueryString Route';
  }
  if (typeof beroutes[uri][key] != 'function') {
    throw 'Callable Function';
  }

  if (hists) {
    history.pushState('', title, url);
  }

  beroutes[uri][key].call(this, uri, key, value);
}
