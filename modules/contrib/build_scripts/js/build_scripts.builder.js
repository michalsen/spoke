/**
 * @file
 * Streams log output from build job.
 */

(function (once, Drupal, drupalSettings) {
  /**
   *
   * @param {ReadableStreamDefaultReader} reader
   * @param {(data) => void} callback
   */
  async function readStream(reader, callback) {
    const decoder = new TextDecoder();

    while (true) {
      const { value, done } = await reader.read();

      console.log("received", value);

      const newData = decoder.decode(value, { stream: !done });

      callback(newData);

      if (done) {
        return;
      }
    }
  }

  /**
   * Stream build log output.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the functionality to page.
   */
  Drupal.behaviors.oeBuilder = {
    attach(context) {
      const { buildId, endpoint } = drupalSettings.build_scripts;

      const element = once("builder", "#builder-output", context);
      if (element.length) {
        fetch(endpoint).then(response => {
          const reader = response.body.getReader();

          // Clear previous output.
          element[0].textContent = "";

          return readStream(reader, chunk => {
            element[0].textContent += chunk;
          });
        });
      }
    }
  };
})(once, Drupal, drupalSettings);
