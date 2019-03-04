function setupNavToggle() {
  function handleClick(evt) {
    this.parentNode.classList.toggle('s-mobile-closed');
    if (this.textContent === 'Valikko') {
      this.textContent = 'Sulje';
    } else {
      this.textContent = 'Valikko';
    }
  }

  var elements = document.querySelectorAll('.c-navigation');
  Array.prototype.forEach.call(elements, function(el, i) {
    var toggleButton = document.createElement('button');

    toggleButton.setAttribute('aria-hidden', 'true');
    toggleButton.setAttribute('class', 'js-navbar-toggle c-button');
    toggleButton.textContent = 'Valikko';
    toggleButton.addEventListener('click', handleClick);

    var h1 = el.querySelectorAll('h1')[0];
    h1.insertAdjacentElement('afterend', toggleButton);
    el.classList.toggle('s-mobile-closed');
  });
}

setupNavToggle();
