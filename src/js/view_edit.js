/*
 * backend/view_edit.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

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
  const body = 'user_id=' + value;
  //-TEMP
  const request = api_request(method, endpoint, body);

  const form = view.querySelector('form');
  const fieldset_ph = form.firstElementChild;

  function render(data) {
    var i = 0;

    const fieldset = document.createElement('fieldset');

    for (const idx in data) {
      for (const field in data[idx]) {
        const row = data[idx][field];

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

      i++;
    }

    form.classList.remove('placeholder');
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
      console.error('view_edit()', 'load()', err);

      error();
    }
  }

  function error(xhr) {
    console.error('view_edit()', 'error()', xhr);
  }

  request.then(load).catch(error);

  view.removeAttribute('hidden');
}
