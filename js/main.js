// Otwieranie / zamykanie okna modalnego z formularzem.
function openModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.add('show');
}
function closeModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.remove('show');
}

// Zamkniecie modala klikiem w tlo.
document.addEventListener('click', function (ev) {
  if (ev.target.classList.contains('modal-bg')) {
    ev.target.classList.remove('show');
  }
});
