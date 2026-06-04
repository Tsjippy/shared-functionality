async function getNonce() {
  let formData = new FormData();
  formData.append("_wpnonce", tsjippy.restNonce);

  let result;
  try {
    result = await fetch(
      `${tsjippy.baseUrl}/wp-json${tsjippy.restApiPrefix}/fetch_nonce`,
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

  let json = JSON.parse(response);

  window.tsjippy.restNonce = json;
}

getNonce();
