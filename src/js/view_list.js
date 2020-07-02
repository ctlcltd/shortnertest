/*
 * backend/view_list.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

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

    let id = '#';
    const tr_tpl = tbody.firstElementChild;

    for (const idx in data) {
      const tr = tr_tpl.cloneNode(true);
      const tr_ph = tr.firstElementChild;
      const action_edit = tr.querySelector('td.action-edit > a');
      const action_delete = tr.querySelector('td.action-delete > a');

      for (const field in data[idx]) {
        const row = data[idx][field];

        if (field.indexOf('_id') != -1) {
          id = row.toString();
        }

        if (i === 0) {
          const th = document.createElement('th');
          th.innerText = field;
          thead.firstElementChild.insertBefore(th, thead.firstElementChild.lastElementChild);
        }

        const td = document.createElement('td');
        td.innerText = row ? row.toString() : '';
        tr.insertBefore(td, tr_ph);
      }

      action_edit.href += id;
      action_delete.href += id;
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
        return error();
      }

      if (obj.data) {
        render(obj.data);
      }
    } catch (err) {
      console.error('view_list()', 'load()', err);

      error();
    }
  }

  function error(xhr) {
    console.error('view_list()', 'error()', xhr);
  }

  request.then(load).catch(error);

  view.removeAttribute('hidden');
}
