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


function main() {
  const view = document.getElementById('main');
  // const menu = view.querySelector('.nav.placeholder');
  const menu = view.querySelector('.nav');

  nav(menu);

  view.removeAttribute('hidden');
}


function view_list(uri, key, value) {
  const source = document.querySelector('.view-list');
  const clone = source.cloneNode(true);
  clone.removeAttribute('class');
  clone.setAttribute('id', 'view-list');
  clone.cloned = true;
  document.body.insertBefore(clone, source);

  const view = document.getElementById('view-list');
  const menu = view.querySelector('.nav.placeholder');
  const heading = view.querySelector('h2');

  nav(menu);

  heading.innerText = uri + ' list';
  heading.className = '';

  const endpoint = '/' + uri;
  const method = 'get';
  const request = api_request(method, endpoint);

  const table = view.querySelector('table');
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');

  function actionEdit(evt) {
    evt.preventDefault();

    if (evt.target.parentElement.classList.contains('action-edit') || evt.target.tagName == 'TD') {
      route(this.dataset.href);
    }

    return false;
  }

  function actionDelete(evt) {
    evt.preventDefault();

    if (window.confirm('Are you sure to delete this item?')) {
      route(this.href);
    }

    return false;
  }

  function render(data) {
    var i = 0;

    let get_id;
    const tr_tpl = tbody.firstElementChild;

    for (const idx in data) {
      const tr = tr_tpl.cloneNode(true);
      const tr_ph = tr.firstElementChild;
      const action_edit = tr.querySelector('td.action-edit > a');
      const action_delete = tr.querySelector('td.action-delete > a');

      for (const field in data[idx]) {
        const row = data[idx][field];

        //-TEMP
        if (! get_id && field.indexOf('_id') != -1) {
          get_id = '&' + field + '=' + row.toString();
        }

        if (i === 0) {
          const th = document.createElement('th');
          th.innerText = field;
          thead.firstElementChild.insertBefore(th, thead.firstElementChild.lastElementChild);
        }
        //-TEMP

        const td = document.createElement('td');
        td.innerText = row ? row.toString() : '';
        tr.insertBefore(td, tr_ph);
      }

      //-TEMP
      action_edit.href += get_id;
      action_delete.href += get_id;
      //-TEMP

      action_delete.onclick = actionDelete;

      tr.setAttribute('data-href', action_edit.href);
      tr.onclick = actionEdit;

      tbody.prepend(tr);

      i++;
    }

    tr_tpl.remove();
    table.classList.remove('placeholder');
  }

  function load(xhr) {
    try {
      const obj = JSON.parse(xhr.response);

      if (! obj.status) {
        return error(obj.data);
      }

      if (obj.data) {
        render(obj.data);
      }
    } catch (err) {
      console.error('view_list()', 'load()', err);

      error(null, err);
    }
  }

  function error(xhr, err) {
    console.error('view_list()', 'error()', xhr || '', err || '');
  }

  request.then(load).catch(error);

  view.removeAttribute('hidden');
}


function view_edit(uri, key, value) {
  const source = document.querySelector('.view-edit');
  const clone = source.cloneNode(true);
  clone.removeAttribute('class');
  clone.setAttribute('id', 'view-edit');
  clone.cloned = true;
  document.body.insertBefore(clone, source);

  const view = document.getElementById('view-edit');
  const menu = view.querySelector('.nav.placeholder');
  const heading = view.querySelector('h2');

  nav(menu);

  heading.innerText = uri + ' edit';
  heading.className = '';

  const endpoint = '/' + uri;
  //-TEMP
  const method = 'put';
  const body = value;
  //-TEMP
  const request = api_request(method, endpoint, body);

  const form = view.querySelector('form');
  const fieldset_ph = form.firstElementChild;

  function render(data) {
    const fieldset = document.createElement('fieldset');

    for (const field in data) {
      const row = data[field];

      const div = document.createElement('div');
      const label = document.createElement('label');
      const input = document.createElement('input');

      label.innerText = field;        
      input.setAttribute('type', 'text');
      input.value = row ? row.toString() : '';

      div.append(label);
      div.append(input);

      fieldset.append(div);

      form.insertBefore(fieldset, fieldset_ph);
    }

    form.classList.remove('placeholder');
  }

  function load(xhr) {
    try {
      const obj = JSON.parse(xhr.response);

      if (! obj.status) {
        return error(obj.data);
      }

      if (obj.data) {
        render(obj.data);
      }
    } catch (err) {
      console.error('view_edit()', 'load()', err);

      error(false, err);
    }
  }

  function error(xhr, err) {
    console.error('view_edit()', 'error()', xhr || '', err || '');
  }

  request.then(load).catch(error);

  view.removeAttribute('hidden');
}


function signin() {
  const view = document.getElementById('signin');
  const form = document.getElementById('sign_login');
  const placeholder = form.querySelector('.placeholder').cloneNode();
  const attempts_limit = 3;
  let attempts = 0, err_log = null;

  function submit(evt) {
    evt.preventDefault();

    form.setAttribute('disabled', '');
    form.setAttribute('data-loading', '');

    err_log && err_log.replaceWith(placeholder);

    let body = [];

    for (const el of this.elements) {
      if (el.tagName != 'FIELDSET' && el.tagName != 'BUTTON' && ! (el.type && el.type === 'button')) {
        body.push(el.name + '=' + el.value);
      }
    }

    if (body.length) {
      body = body.join('&');
    } else {
      form.removeAttribute('disabled');
      form.removeAttribute('data-loading');

      return error(null, 'Please enter your credentials.');
    }

    const login = api_request('post', '/', body);

    login.then(next).catch(error);
  }

  function next(xhr) {
    form.reset();

    try {
      const obj = JSON.parse(xhr.response);

      if (obj.status && obj.data) {
        form.removeAttribute('disabled');
        form.removeAttribute('data-loading');

        view.setAttribute('hidden', '');

        var session_value = '1; ';
        var session_path = 'path=' + bepath + '; ';
        var session_secured = ''; //'secure; ';
        var session_expires = 'expires=' + new Date(new Date().getTime() + (60 * 60 * 24 * 1e3)).toGMTString() + '; ';

        console.log('signin()', 'next()', 'urls-sign=' + session_value + session_path + session_expires + session_secured + 'samesite=strict');

        document.cookie = 'urls-sign=' + session_value + session_path + session_expires + session_secured + 'samesite=strict';

        return route(bepath + '/');
      } else {
        if (attempts++ < attempts_limit) {
          form.removeAttribute('disabled');
        }

        throw 'Wrong credentials.';
      }
    } catch (err) {
      form.removeAttribute('data-loading');

      error(xhr, err);
    }
  }

  function error(xhr, msg) {
    form.reset();

    if (xhr && ! msg && xhr.status) {
      msg = 'An error occurred.';
    }

    err_log = document.createElement('div');
    err_log.className = 'error';
    err_log.innerText = msg;

    form.querySelector('.placeholder').replaceWith(err_log);

    console.error('signin()', 'error()', msg);
  }

  document.cookie = 'urls-sign=; expires=' + new Date(0).toGMTString() + ', samesite=strict';

  form.addEventListener('submit', submit);

  view.removeAttribute('hidden');
}


function signout() {
  return route(bepath + '/?login');
}


function api_request(method, endpoint, body) {
  const xhr = new XMLHttpRequest();
  let url = apipath + endpoint;

  if (url.substr(-1) != '/') {
    url += '/';
  }

  if (body && method === 'get') {
    url += '?' + body;
    body = null;
  }

  xhr.open(method, url);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.send(body);

  return new Promise(function(resolve, reject) {
    xhr.onload = function() { resolve(xhr); };
    xhr.onerror = function() { reject(xhr); };
  });
}
/*!
 * backend/api_test.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

function api_test() {
  const view = document.getElementById('api-test');
  // const menu = view.querySelector('.nav.placeholder');
  const menu = view.querySelector('.nav');
  const request_form = document.getElementById('api_request');
  const response_form = document.getElementById('api_response');
  const methods_api = api_request('get', '/', '');
  const endpoint_select = request_form.querySelector('[name="endpoint"]');
  const method_select = request_form.querySelector('[name="method"]');
  const route_input = request_form.querySelector('[name="route"]');
  const body_input = request_form.querySelector('[name="body"]');
  const response_status = response_form.querySelector('[name="response-status"]');
  const response_body = response_form.querySelector('[name="response-body"]');
  const response_headers = response_form.querySelector('[name="response-headers"]');
  let routes;

  nav(menu);

  function response(xhr) {
    console.log('api_test()', 'response()', xhr);

    response_form.removeAttribute('data-loading');

    response_status.value = xhr.status;
    response_body.value = xhr.response;
    response_headers.value = xhr.getAllResponseHeaders();
  }

  function load(xhr) {
    console.log('api_test()', 'load()', xhr);

    try {
      view.removeAttribute('hidden');

      const obj = JSON.parse(xhr.response);

      if (! obj.status) {
        return error();
      }

      routes = obj.data;

      for (const endpoint in obj.data) {
        let option;
        option = document.createElement('option'), option.value = endpoint, option.innerText = endpoint;
        endpoint_select.appendChild(option);
      }

      endpointChange();
      methodChange();
    } catch (err) {
      console.error('api_test()', 'load()', err);
    }
  }

  function error() {
    view.setAttribute('hidden', '');

    return route(bepath + '/?login');
  }

  function requestSubmit(evt) {
    evt.preventDefault();

    const method = method_select.value;
    const endpoint = endpoint_select.value;
    const body = body_input.value;

    const request = api_request(method, endpoint, body);

    response_form.setAttribute('data-loading', '');

    request.then(response).catch(response);
  }

  function requestReset(evt) {
    evt.preventDefault();

    body_input.value = '';
  }

  function endpointChange() {
    try {
      if (! routes) throw 0;

      const endpoint = endpoint_select.value;

      method_select.innerHTML = '';

      for (const method in routes[endpoint]) {
        let option;
        option = document.createElement('option'), option.value = method.toLowerCase(), option.innerText = method;
        method_select.appendChild(option);
      }

      methodChange();
    } catch (err) {
      console.error('api_test()', 'endpointChange()', err);
    }
  }

  function methodChange() {
    try {
      if (! routes) throw 0;

      const endpoint = endpoint_select.value;
      const method = method_select.value.toUpperCase();

      route_input.value = JSON.stringify(routes[endpoint][method]);
    } catch (err) {
      console.error('api_test()', 'methodChange()', err);
    }
  }

  request_form.addEventListener('submit', requestSubmit);
  request_form.addEventListener('reset', requestReset);
  endpoint_select.addEventListener('change', endpointChange);
  method_select.addEventListener('change', methodChange);

  methods_api.then(load).catch(load);
}


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
