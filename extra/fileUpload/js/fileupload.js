import { addCropper } from "./partials/image-edit.js";
import { createProgressBar } from "./partials/file-upload-exports.js";

export { createProgressBar };

console.log("Fileupload.js loaded");

let totalFiles = 0;
var fileUploadWrap = "";
export let fileTypeFilter = {};

function addPreview(link, value, nonce) {
  let name = fileUploadWrap
    .querySelector(".file-upload")
    .name.replace("-files", "");

  let loaderHtml = Main.showLoader(null, false, 50, "", true);

  let html = `
	<div class='document'>
		<input type='hidden' class='no-reset' name='url' value='${value}' data-pending=1>
    <input type='hidden' class='no-reset' name='nonce' value='${nonce}'>
		${link}
		${loaderHtml}
	</div>`;

  // insert html
  fileUploadWrap
    .querySelector(".document-preview")
    .insertAdjacentHTML("beforeend", html);

  return fileUploadWrap.querySelector(".document-preview .document");
}

async function startFileUpload(target) {
  target.classList.add("active");

  let s          = "";
  fileUploadWrap = target.closest(".file-upload-wrap");
  totalFiles     = target.files.length;

  if (totalFiles < 0) {
    Main.displayMessage(
      "Please select some files before hitting the uplad button!",
      "error",
    );
    return;
  }

  // prevent form submission
  let form = target.closest("form");
  if (form != undefined) {
    form.querySelector(".button.form-submit").disabled = true;
  }

  //Hide upload button if only one file allowed
  if (!fileUploadWrap.querySelector(".file-upload").multiple) {
    fileUploadWrap.querySelector(".upload-div").classList.add("hidden");
  }

  // make the target no longer required
  target.required = false;

  //Create a formData element
  let formData = new FormData();

  //Add the ajax action name
  formData.append("action", "upload-files");

  //Loop over the dataset attributes and add them to data
  target.parentNode.querySelectorAll("input").forEach((input) => {
    formData.append(input.name, input.value);
  });

  //Add all the files to the formData
  for (let index = 0; index < totalFiles; index++) {
    let file = target.files[index];
    let type = file.type.split("/")[0];

    // Check if a function is registered for the file type, if so call it and skip default upload
    // TO DO Test with working Vimeo API
    if (typeof fileTypeFilter[type] == "function") {
      fileTypeFilter[type]();
      // file to big
    } else if (file.size > tsjippy.maxFileSize) {
      Main.displayMessage(
        "File too big, max file size is " +
          parseInt(tsjippy.maxFileSize) / 1024 / 1024 +
          "MB",
        "error",
      );

      target.value = "";
      return;
    } else if (type == "image" && target.matches(".should-edit")) {
      file = await addCropper(file);

      formData.append("files[]", file);
    } else {
      formData.append("files[]", file);
    }
  }

  // No files remaining to upload after uploading videos
  if (formData.get("files[]") == null) {
    target.closest("form").querySelector(".button.form-submit").disabled =
      false;

    target.classList.remove("active");

    return;
  }

  //AJAX request
  let request = new XMLHttpRequest();

  //Listen to the state changes
  request.onreadystatechange = readyStateChanged;

  //Listen to the upload status
  request.upload.addEventListener("progress", fileUploadProgress, false);

  request.open("POST", tsjippy.ajaxUrl, true);

  //Create a progressbar
  createProgressBar(target);

  //Send AJAX request
  if (totalFiles > 1) {
    s = "s";
  }
  Main.showLoader(
    document.getElementById("progress-wrapper"),
    false,
    100,
    "Uploading document" + s,
  );

  request.send(formData);
}

function fileUploadProgress(e) {
  if (e.lengthComputable) {
    var max = e.total;
    var current = e.loaded;

    var percentage = (current * 100) / max;
    percentage = Math.round(percentage * 10) / 10;

    document
      .querySelectorAll("#upload-progress")
      .forEach((el) => (el.value = percentage));
    document
      .querySelectorAll("#progress-percentage")
      .forEach((el) => (el.textContent = `   ${percentage}%`));

    if (percentage >= 99) {
      //Remove progress barr
      document.getElementById("progress-wrapper").remove();

      // process completed
      fileUploadWrap.querySelectorAll(".loader-text").forEach((el) => {
        //Change message text
        if (totalFiles > 1) {
          el.textContent = "Processing documents";
        } else {
          el.textContent = "Processing document";
        }
      });
    }
  }
}

function readyStateChanged(e) {
  let request = e.target;

  //If finished
  if (request.readyState == 4) {
    //Success
    if (request.status >= 200 && request.status < 400) {
      fileUploadSucces(request.responseText);
      //Error
    } else {
      console.error(request.responseText);
      Main.displayMessage(JSON.parse(request.responseText).error, "error");
    }

    // Remove loaders
    document.querySelectorAll(".loader-wrapper").forEach((el) => el.remove());

    //Clear the input
    fileUploadWrap.querySelector(".file-upload").value = "";

    // enable form submission again
    document
      .querySelectorAll(".button.form-submit[disabled]")
      .forEach((el) => (el.disabled = false));
  }
}

function fileUploadSucces(result) {
  let response  = JSON.parse(result);
  let nonce     = response.nonce;
  let imgUrls   = response.urls;

  let src = "";

  for (const element of imgUrls) {
    src = element["url"];
    let url = tsjippy.baseUrl + "/" + src;
    let value = "";
    let anchorLink = "";

    if (element["id"] == undefined) {
      value = url;
    } else {
      value = element["id"];
    }

    //is image
    if (src.toLowerCase().match(/\.(jpe|jpeg|jpg|gif|png|webp)/) != null) {
      // Add a version
      url += `?version=${Math.floor(Math.random() * 1000)}`;

      //Add the image
      anchorLink = `<a class="fileupload" href="${url}"><img src="${url}" alt="picture" style="height:150px;"></a>`;
    } else if (src.toLowerCase().match(/\.pdf/) != null) {
      anchorLink = `<object data='${url}' 
					type='application/pdf' 
					width='100%' height='400px'>
				</object>`;
    } else {
      //Add a link
      let filename = url.split("/")[url.split("/").length - 1];
      anchorLink = `<a class="fileupload" href="${url}">${filename}</a>`;
    }
    anchorLink += `<button type="button" class="remove-document button">X</button>`;

    addPreview(anchorLink, value, nonce);
  }

  // remove Loader
  fileUploadWrap.querySelector(".progress-wrapper");

  if (imgUrls.length == 1) {
    let fileName = src.split("/")[src.split("/").length - 1];
    Main.displayMessage(
      `The file ${fileName} has been uploaded succesfully.`,
      "success",
      1500,
    );
  } else {
    Main.displayMessage(
      "The files have been uploaded succesfully.",
      "success",
      1500,
    );
  }

  // Create a custom event so others can listen to it.
  // Used by formstable uploads
  const event = new Event("uploadfinished");
  fileUploadWrap.dispatchEvent(event);

  document
    .querySelectorAll(".file-upload.active")
    .forEach((el) => el.classList.remove("active"));
}

async function removeDocument(target) {
  let formData = new FormData();

  //Loop over the dataset attributes and add them to post
  target.parentNode.querySelectorAll("input").forEach((input) => {
    formData.append(input.name, input.value);
  });

  //hide the remove button
  target.style.display = "none";

  //add an id to the remove button so we can query for it later
  target.parentNode.classList.add("remove-this-document");

  let docWrapper = target.closest(".document");
  fileUploadWrap = docWrapper.closest(".file-upload-wrap");

  //show loader
  let loader = Main.showLoader(
    target.closest(".document"),
    true,
    100,
    "Deleting Image",
  );

  let response = await FormSubmit.fetchRestApi("remove-document", formData);

  if (response) {
    // If successful
    try {
      //show the upload field
      fileUploadWrap.querySelector(".upload-div").classList.remove("hidden");
    } catch {
      //remove featured image id
      document.querySelector('[name="post-image-id"]').value = "";
    }

    //remove the document/picture
    docWrapper.remove();

    //hide the loading gif
    loader.remove();

    //var re = new RegExp('(.*)[0-9](.*)',"g");
    //reindex documents
    fileUploadWrap.querySelectorAll(".file_url").forEach((el, index) => {
      //el.name = el.name.replace(re, '$1'+index+'$2');
      el.name = el.name.replace(/(\d)/g, index);
    });

    Main.displayMessage(response);
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  // We should load the image editor, and it is not yet there
  if (
    document.querySelector(`.image-edit-modal-trigger`) != null &&
    document.querySelector(`#edit-image-modal`) == null
  ) {
    // immideately create an empty div to prevent duplicates
    document.body.insertAdjacentHTML(
      "beforeend",
      `<div id="edit-image-modal" class="modal edit-image hidden"></div>`,
    );

    document
      .querySelectorAll(`.image-edit-modal-trigger`)
      .forEach((el) => el.remove());

    let response = await FormSubmit.fetchRestApi("fetch_image_edit_modal");

    if (response) {
      document.getElementById("edit-image-modal").outerHTML = response;
    }
  }
});

//Remove picture on button click
window.addEventListener("click", function (event) {
  var target = event.target;

  if (target.matches(".remove-document")) {
    event.stopImmediatePropagation();
    removeDocument(target);
  }
});

window.addEventListener("change", (event) => {
  let target = event.target;

  if (target.className.includes("file-upload")) {
    event.stopImmediatePropagation();

    // TO DO Test with working Vimeo API
    startFileUpload(target);
  }
});
