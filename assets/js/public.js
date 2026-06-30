document.addEventListener('DOMContentLoaded', function () {
 const refreshTargets = [];

 const app = document.getElementById('app');
 if (app) {
  refreshTargets.push(app);
 }

 document.querySelectorAll('[data-bso-scoreboard]').forEach(function (node) {
  refreshTargets.push(node);
 });

 if (!refreshTargets.length) {
  return;
 }

 function renderMessage(type, text) {
  return '<p class="bso-dashboard-message bso-dashboard-message--' + type + '">' + text + '</p>';
 }

 function loadTarget(container) {
  const params = new URLSearchParams({ action: 'bso_dashboard_data' });
  const gameId = container.dataset.gameId || '';

  if (gameId && Number(gameId) > 0) {
   params.set('game_id', gameId);
  }

  fetch(ajaxurl + '?' + params.toString())
   .then(function (response) {
    if (!response.ok) {
     throw new Error('HTTP ' + response.status);
    }
    return response.json();
   })
   .then(function (payload) {
    if (payload && payload.success && payload.data && payload.data.html) {
     container.innerHTML = payload.data.html;
     return;
    }

    container.innerHTML = renderMessage('warning', 'Er is momenteel geen scoredata beschikbaar.');
   })
   .catch(function () {
    container.innerHTML = renderMessage('error', 'Scoredata kon niet worden geladen. Controleer je verbinding en probeer opnieuw.');
   });
 }

 function loadAll() {
  refreshTargets.forEach(loadTarget);
 }

 loadAll();
 setInterval(loadAll, 5000);
});
