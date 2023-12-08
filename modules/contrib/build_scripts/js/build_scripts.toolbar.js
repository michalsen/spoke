/**
 * @file
 * Modifies builder toolbar links.
 */

(function (once, Drupal) {
  /**
   * https://stackoverflow.com/a/35385518
   *
   * @param {String} HTML representing a single element
   * @return {Element}
   */
  function htmlToElement(html) {
    var template = document.createElement('template');
    html = html.trim(); // Never return a text node of whitespace as the result
    template.innerHTML = html;
    return template.content.firstChild;
  }

  /**
   * Starts build when links in the toolbar is clicked.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the functionality to the toolbar-builder link.
   */
  Drupal.behaviors.oeBuilderToolbar = {
    attach(context) {
      once("builder", "[data-toolbar-builder]", context).forEach(el => {
        const url = el.href;

        el.addEventListener('click', event => {
          event.preventDefault();
          const progressIndicator = htmlToElement(Drupal.theme.ajaxProgressIndicatorFullscreen());

          document.body.after(progressIndicator);

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
          }).then(() => {
            console.log("Build started");

            // Redirect to logs
            window.location.href = url;
          }).catch(() => {
            progressIndicator.remove();
          });
        })
      })
    }
  };
})(once, Drupal);
