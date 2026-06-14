import { addCropper } from "./partials/image-edit.js";
import { createProgressBar } from "./partials/file-upload-exports.js";

export { createProgressBar };

console.log("Fileupload.js loaded");

let totalFiles       = 0;
var uploadDiv        = "";
export let fileTypeFilter = {};

async function startFileUpload(target) {
  target.classList.add("active");

  let s          = "";
  uploadDiv      = target.closest(".upload-div");
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
    form.querySelectorAll(".button.form-submit").forEach(el => el.disabled = true);
  }

  //Hide upload button if only one file allowed
  if (!uploadDiv.multiple) {
    uploadDiv.classList.add("hidden");
  }

  // make the target no longer required
  target.required = false;

  //Create a formData element
  let formData = new FormData();

  //Add the ajax action name
  formData.append("action", "tsjippy-upload-files");

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
    target.closest("form").querySelectorAll(".button.form-submit").forEach(el => el.disabled = false);

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
    percentage     = Math.round(percentage * 10) / 10;

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
      uploadDiv.querySelectorAll(".loader-text").forEach((el) => {
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
    uploadDiv.value = "";

    // enable form submission again
    document
      .querySelectorAll(".button.form-submit[disabled]")
      .forEach((el) => (el.disabled = false));
  }
}

function fileUploadSucces(result) {
  let response  = JSON.parse(result);
  let html      = response.html;
  let message   = response.message;

  uploadDiv.closest(`.file-upload-wrap`).outerHTML = html;

  // remove Loader
  uploadDiv.closest(`.file-upload-wrap`).querySelector(".progress-wrapper");

  Main.displayMessage(
    message,
    "success",
    1500,
  );

  // Create a custom event so others can listen to it.
  // Used by formstable uploads
  const event = new Event("uploadfinished");
  uploadDiv.closest(`.file-upload-wrap`).dispatchEvent(event);

  document
    .querySelectorAll(".file-upload.active")
    .forEach((el) => el.classList.remove("active"));
}

async function removeDocument(target) {

  /**
   * Removing a non uploaded file
   */
  if(target.matches(`.local`)){

    let fileInput   = target.closest('.file-upload-wrap').querySelector(`input[type='file']`);

    /**
     * Remove file from input by recreating the filelist without the one we just removed
     */
    const dt = new DataTransfer()

    for (let file of fileInput.files)
      if (file !== fileInput.files[target.dataset.index]) 
        dt.items.add(file)

    fileInput.files = dt.files

    target.closest('.document').remove();
    return;
  }

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

  uploadDiv      = docWrapper.closest(".file-upload-wrap");

  let prevHtml   = uploadDiv.closest(`.file-upload-wrap`).innerHTML;

  //show loader
  let loader = Main.showLoader(
    docWrapper,
    true,
    100,
    "Deleting Image",
  );

  let response = await FormSubmit.fetchRestApi("remove-document", formData);

  if (response) {
    // If successful
    try {
      //show the upload field
      uploadDiv.querySelector(".upload-div").classList.remove("hidden");
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
    uploadDiv.querySelectorAll(".file_url").forEach((el, index) => {
      //el.name = el.name.replace(re, '$1'+index+'$2');
      el.name = el.name.replace(/(\d)/g, index);
    });

    Main.displayMessage(response);
  }else{
    uploadDiv.closest(`.file-upload-wrap`).innerHTML = prevHtml;
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

    /**
     * Add a preview
     */
    Array.from(target.files).forEach( (file, index) => {
      // Add a document preview
      let wrapper   = document.createElement('div');
      wrapper.classList.add('document');
      wrapper.style.display        = "flex";
      wrapper.style.alignItems     = "center";
      wrapper.style.justifyContent = "center";
      
      target.closest(`.file-upload-wrap`).querySelector(".document-preview").appendChild(wrapper);

      let fileType = file.type.split("/");

      if(fileType[1] == 'pdf'){
        fileType  = 'pdf'
      }else{
        fileType  = fileType[0];
      }

      let preview, source;

      let url      = URL.createObjectURL(file);

      if(fileType == 'image'){
        preview = document.createElement('img');
        preview.src = url;
      }else if(fileType == 'video'){
        preview = document.createElement('video');
        preview.controls = true;
        preview.width  = "100%";
        preview.height = "360";

        source = document.createElement('source');
        source.src = url;
        source.type = file.type;

        preview.appendChild(source);
      }else if(fileType == 'pdf'){
        preview = document.createElement('embed');
        preview.src    = url;
        preview.type   = file.type;
        preview.style.width  = "calc(100% - 50px)";
        preview.height = "360";
      }else if(fileType == 'audio'){
        preview          = document.createElement('audio');
        preview.controls = true;

        source           = document.createElement('source');
        source.src       = url;
        source.type      = file.type;

        preview.appendChild(source);
      }else{
        wrapper.style.display  = "initial";
        preview                = document.createElement('a');
        preview.src            = url;
        preview.target         = '_blank';
        preview.textContent    = file.name.replace(/\.[^/.]+$/, "");
      }

      wrapper.appendChild(preview);

      let button            = document.createElement('button');
      button.classList.add('remove-document', 'button', 'small', 'local');
      button.type           = 'button';
      button.innerText      = 'X';
      button.dataset.index  = index;

      wrapper.appendChild(button);
    });

    /**
     * Only upload if not defered
     */
    if(!target.matches(`.defer-upload`)){
      // TO DO Test with working Vimeo API
      startFileUpload(target);
    }
  }
});
