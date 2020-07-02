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
