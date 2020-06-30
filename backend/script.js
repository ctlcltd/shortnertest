function signin() {
  const view = document.getElementById('signin');

  view.style = 'display: block';
}

function api_request(method, endpoint, body) {
  console.log('api_request()', { method, endpoint, body });

  const xhr = new XMLHttpRequest();
  let url = endpoint;

  if (url.substr(-1) != '/') {
    url += '/';
  }

  if (method === 'get') {
    url += '?' + body;
    body = null;
  }

  xhr.open(method, url);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.send(body);

  return new Promise(function(resolve, reject) {
    xhr.onload = function() { resolve(xhr); }
    xhr.onerror = function() { reject(xhr); }
  });
}

function api_test() {
  const view = document.getElementById('api-test');
  const request_form = document.getElementById('api_request');
  const response_form = document.getElementById('api_response');
  const methods = api_request('get', '/api', '');
  const endpoint_select = request_form.querySelector('[name="endpoint"]');
  const method_select = request_form.querySelector('[name="method"]');
  const route_input = request_form.querySelector('[name="route"]');
  const body_input = request_form.querySelector('[name="body"]');
  const response_status = response_form.querySelector('[name="response-status"]');
  const response_body = response_form.querySelector('[name="response-body"]');
  const response_headers = response_form.querySelector('[name="response-headers"]');

  let routes;

  function response(xhr) {
    console.info('api_test()', 'response()', xhr);

    response_status.value = xhr.status;
    response_body.value = xhr.response;
    response_headers.value = xhr.getAllResponseHeaders();
  }

  function submit(evt) {
    evt.preventDefault();

    const method = method_select.value;
    const endpoint = '/api' + endpoint_select.value;
    const body = body_input.value;

    const request = api_request(method, endpoint, body);

    request.then(response).catch(response);
  }

  function load(xhr) {
    console.info('api_test()', 'load()', xhr);

    try {
      view.style = 'display: block';

      const obj = JSON.parse(xhr.response);

      if (! obj.status) throw 0;

      routes = obj.data;

      for (const endpoint in obj.data) {
        let option;
        option = document.createElement('option'), option.value = endpoint, option.innerText = endpoint;
        endpoint_select.appendChild(option);
      }

      endpointChange();
      methodChange();
    } catch (err) {
      console.error(err);

      view.style = 'display: none';

      signin();
    }
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
    } catch (err) {
      console.error(err);
    }
  }

  function methodChange() {
    try {
      if (! routes) throw 0;

      const endpoint = endpoint_select.value;
      const method = method_select.value.toUpperCase();

      route_input.value = JSON.stringify(routes[endpoint][method]);
    } catch (err) {
      console.error(err);
    }
  }

  request_form.addEventListener('submit', submit);
  endpoint_select.addEventListener('change', endpointChange);
  method_select.addEventListener('change', methodChange);

  methods.then(load).catch(load);
}

function init() {
  api_test();
}

init();