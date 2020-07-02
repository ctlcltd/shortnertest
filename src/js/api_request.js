/*
 * backend/api_request.js
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

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
