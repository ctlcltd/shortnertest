/*
 * backend/signin.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

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
