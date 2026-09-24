console.log('Nonce script loaded');

async function getNonce() {
  const data   = JSON.parse(
    document.getElementById(
        'wp-script-module-data-@tsjippy/nonce_script'
    ).textContent
  );

  let formData = new FormData();
  formData.append("_wpnonce", data.restNonce);

  let result;
  try {
    result = await fetch(
      `${data.baseUrl}/wp-json/tsjippy/v2/fetch_nonce`,
      {
        method: "POST",
        credentials: "same-origin",
        body: formData,
      },
    );
  } catch (error) {
    console.error(error);
  }

  let response = await result.text();

  const newNonce = JSON.parse(response);

  document
      .querySelectorAll('script[type="application/json"]')
      .forEach(script => {
          try {
              const data = JSON.parse(script.textContent);

              if ('restNonce' in data) {
                  data.restNonce = newNonce;
                  script.textContent = JSON.stringify(data);
              }
          } catch (e) {
              // Not valid JSON, skip
          }
      });
}

getNonce();
